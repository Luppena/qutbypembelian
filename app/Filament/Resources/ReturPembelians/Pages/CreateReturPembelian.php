<?php

namespace App\Filament\Resources\ReturPembelians\Pages;

use App\Filament\Resources\ReturPembelians\ReturPembelianResource;
use App\Models\DaftarAkun;
use App\Models\JurnalUmum;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateReturPembelian extends CreateRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan')
            ->disabled(fn (): bool => $this->hasInvalidReturForm());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dibuat_oleh'] = auth()->id();
        $data['status'] = ($data['penyelesaian'] ?? null) === 'uang_potong_tagihan'
            ? 'refund_diproses'
            : 'tukar_barang_diproses';

        if (empty($data['details'])) {
            $data['details'] = ReturPembelianResource::getAutoReturDetails($data['pembelian_id'] ?? null);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $retur = $this->getRecord()->load(['details', 'pembelian']);

        if ($retur->penyelesaian === 'barang_pengganti') {
            $retur->pembelian?->update(['status' => 'retur']);
            return;
        }

        if ($retur->penyelesaian === 'uang_potong_tagihan') {
            $this->createJurnalPotongTagihan($retur);
        }
    }

    private function createJurnalPotongTagihan($retur): void
    {
        if (JurnalUmum::query()
            ->where('transaksi_type', $retur::class)
            ->where('transaksi_id', $retur->id)
            ->exists()) {
            return;
        }

        $totalRetur = (float) $retur->details->sum('subtotal');

        if ($totalRetur <= 0) {
            return;
        }

        $utangUsaha = DaftarAkun::firstOrCreate(
            ['kode_akun' => '211'],
            ['nama_akun' => 'Utang Usaha', 'saldo_normal' => 'kredit']
        );

        $persediaan = DaftarAkun::firstOrCreate(
            ['kode_akun' => '114'],
            ['nama_akun' => 'Persediaan Barang Dagang', 'saldo_normal' => 'debit']
        );

        $jurnal = JurnalUmum::create([
            'tanggal' => $retur->tanggal_retur ?? now(),
            'kode_jurnal' => $this->generateKodeJurnal(),
            'deskripsi' => 'Retur pembelian potong tagihan ' . $retur->nomor_retur,
            'transaksi_type' => $retur::class,
            'transaksi_id' => $retur->id,
        ]);

        $jurnal->details()->createMany([
            [
                'daftar_akun_id' => $utangUsaha->id,
                'posisi' => 'debit',
                'nominal' => $totalRetur,
            ],
            [
                'daftar_akun_id' => $persediaan->id,
                'posisi' => 'kredit',
                'nominal' => $totalRetur,
            ],
        ]);
    }

    private function generateKodeJurnal(): string
    {
        $prefix = 'JU-' . now()->format('Ymd') . '-';
        $last = JurnalUmum::query()
            ->where('kode_jurnal', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('kode_jurnal');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function hasInvalidReturForm(): bool
    {
        $data = $this->data ?? [];

        if (! ReturPembelianResource::poHasConfirmedGrn($data['pembelian_id'] ?? null)) {
            return true;
        }

        $details = $data['details'] ?? [];

        if (empty($details)) {
            return true;
        }

        foreach ($details as $detail) {
            $qtyRetur = (int) ($detail['qty_retur'] ?? 0);
            $qtyDiterima = (int) ($detail['qty_diterima_display'] ?? 0);

            if ($qtyRetur <= 0 || ($qtyDiterima > 0 && $qtyRetur > $qtyDiterima)) {
                return true;
            }
        }

        return false;
    }
}
