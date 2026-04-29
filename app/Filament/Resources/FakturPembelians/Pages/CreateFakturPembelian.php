<?php

namespace App\Filament\Resources\FakturPembelians\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\FakturPembelians\FakturPembelianResource;
use App\Filament\Resources\Pembelian\PembelianResource;
use App\Models\Pembelian;
use App\Models\PembayaranPembelian;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateFakturPembelian extends CreateRecord
{
    use HasBackButtonHeading;


    public array $paymentData = [];

    protected static string $resource = FakturPembelianResource::class;

    public function getTitle(): string
    {
        return 'Tambah Pembayaran';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Bayar');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }

    /**
     * Simpan total yang sudah dihitung di form (amanin tipe data)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_bruto']   = (float) ($data['total_bruto'] ?? 0);
        $data['diskon_persen'] = (float) ($data['diskon_persen'] ?? 0);
        $data['total_netto']   = (float) ($data['total_netto'] ?? 0);

        // Capture payment data
        $this->paymentData = [
            'bank'               => $data['bank'] ?? 'CASH',
            'no_rekening'        => $data['no_rekening'] ?? '-',
        ];

        // Remove from data to prevent SQL unknown column errors
        unset($data['bank'], $data['no_rekening']);

        return $data;
    }

    /**
     * ✅ Setelah faktur dibuat:
     * - tampilkan notifikasi
     * - (opsional) update status pembelian
     */
    protected function afterCreate(): void
    {
        $pembelianId = $this->record?->pembelian_id;

        // ✅ Auto Create Pembayaran Pembelian
        PembayaranPembelian::create([
            'faktur_pembelian_id' => $this->record->id,
            'vendor_id'           => $this->record->vendor_id,
            'bank'                => $this->paymentData['bank'] ?? 'CASH',
            'no_rekening'         => $this->paymentData['no_rekening'] ?? '-',
            'tanggal_pembayaran'  => $this->paymentData['tanggal_pembayaran'] ?? now(),
            'nilai_pembayaran'    => $this->record->total_netto ?? 0,
        ]);

        // ✅ Update Purchase Order status to Lunas & Tambah Stok
        if ($pembelianId) {
            $pembelian = Pembelian::with('details')->find($pembelianId);
            if ($pembelian) {
                $pembelian->update(['status' => 'lunas']);

                if ($pembelian->details) {
                    foreach ($pembelian->details as $detail) {
                        $barang = \App\Models\Barang::find($detail->barang_id);
                        if ($barang) {
                            $barang->increment('stok', $detail->qty);
                        }
                    }
                }
            }
        }

        Notification::make()
            ->title('Faktur & Pembayaran berhasil disimpan')
            ->body('Pesanan pembelian ini telah otomatis berstatus Lunas.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Bayar'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
