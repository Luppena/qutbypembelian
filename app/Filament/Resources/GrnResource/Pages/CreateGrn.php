<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use App\Models\Grn;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateGrn extends CreateRecord
{
    protected static string $resource = GrnResource::class;

    public function getTitle(): string
    {
        return 'Form Penerimaan Barang';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $po = Pembelian::with(['details.grnDetails.grn'])->find($data['pembelian_id'] ?? null);

        if (! $po || ! in_array($po->status, ['menunggu', 'partial', 'retur'], true)) {
            throw ValidationException::withMessages([
                'pembelian_id' => 'Pesanan Pembelian harus berstatus Aktif, Sebagian Diterima, atau Retur.',
            ]);
        }

        if (Carbon::parse($data['tanggal_terima'])->lt($po->tanggal)) {
            throw ValidationException::withMessages([
                'tanggal_terima' => 'Tanggal terima tidak boleh sebelum tanggal Pesanan Pembelian.',
            ]);
        }

        $data['nomor_grn'] = Grn::generateNomor((int) $po->id);

        // Pastikan details disiapkan dari PO jika belum ada
        if (empty($data['details']) && ! empty($data['pembelian_id'])) {
            $data['details'] = GrnResource::getOpenGrnItems($po);
        }

        if (empty($data['details'])) {
            throw ValidationException::withMessages([
                'pembelian_id' => 'Semua item PO ini sudah terpenuhi. Pilih PO lain yang masih memiliki outstanding.',
            ]);
        }

        foreach ($data['details'] as $index => $detail) {
            $poDetail = $po->details->firstWhere('id', $detail['pembelian_detail_id'] ?? null);
            $qtyDiterima = (int) ($detail['qty_diterima'] ?? 0);
            $qtyRusak = (int) ($detail['qty_rusak'] ?? 0);
            $qtyOutstanding = (int) ($poDetail?->qty_outstanding ?? 0);
            $kondisi = (string) ($detail['kondisi'] ?? 'baik');

            if (! $poDetail || (int) ($poDetail->barang_id) !== (int) ($detail['barang_id'] ?? 0)) {
                throw ValidationException::withMessages([
                    "details.{$index}.barang_id" => 'Item penerimaan harus berasal dari detail Pesanan Pembelian.',
                ]);
            }

            if ($qtyDiterima > $qtyOutstanding) {
                throw ValidationException::withMessages([
                    "details.{$index}.qty_diterima" => 'Qty diterima tidak boleh melebihi qty outstanding PO. Kelebihan dicatat sebagai barang titipan.',
                ]);
            }

            if ($qtyRusak > $qtyDiterima) {
                throw ValidationException::withMessages([
                    "details.{$index}.qty_rusak" => 'Qty rusak tidak boleh melebihi qty diterima.',
                ]);
            }

            if (($qtyDiterima < $qtyOutstanding || $kondisi !== 'baik' || $qtyRusak > 0) && blank($detail['catatan_item'] ?? null)) {
                throw ValidationException::withMessages([
                    "details.{$index}.catatan_item" => 'Catatan selisih wajib diisi jika qty kurang atau barang rusak.',
                ]);
            }

            if (($qtyDiterima < $qtyOutstanding || $kondisi !== 'baik' || $qtyRusak > 0) && blank($detail['foto'] ?? null)) {
                throw ValidationException::withMessages([
                    "details.{$index}.foto" => 'Foto kondisi wajib diisi jika qty kurang atau barang rusak.',
                ]);
            }

            unset($data['details'][$index]['qty_sisa'], $data['details'][$index]['qty_sudah_diterima']);
        }

        $data['status_penerimaan'] = $this->determineStatusPenerimaan($data['details'], $po);

        return $data;
    }

    protected function determineStatusPenerimaan(array $details, Pembelian $po): string
    {
        $adaRusak = collect($details)
            ->contains(fn (array $detail): bool => ($detail['kondisi'] ?? 'baik') !== 'baik' || (int) ($detail['qty_rusak'] ?? 0) > 0);

        if ($adaRusak) {
            return 'ada_selisih_retur';
        }

        $adaKurang = collect($details)->contains(function (array $detail) use ($po): bool {
            $poDetail = $po->details->firstWhere('id', $detail['pembelian_detail_id'] ?? null);
            $qtyOutstanding = (int) ($poDetail?->qty_outstanding ?? 0);

            return (int) ($detail['qty_diterima'] ?? 0) < $qtyOutstanding;
        });

        return $adaKurang ? 'sebagian' : 'lengkap';
    }

    protected function getRedirectUrl(): string
    {
        return GrnResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penerimaan Barang')
                ->modalDescription('Jika masih ada sisa qty yang belum diterima, penerimaan ini akan disimpan sebagai penerimaan sebagian dan PO dapat diterima lagi nanti.')
                ->modalSubmitActionLabel('Ya, Simpan')
                ->submit('create'),

            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
