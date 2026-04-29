<?php

namespace App\Filament\Resources\PembayaranPembelians\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\PembayaranPembelians\PembayaranPembelianResource;
use App\Filament\Resources\Pembelian\PembelianResource;
use App\Models\FakturPembelian;
use App\Models\Pembelian;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePembayaranPembelian extends CreateRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PembayaranPembelianResource::class;

    public function getTitle(): string
    {
        return 'Pembayaran Pembelian';
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }

    /**
     * ✅ Pastikan field yang disabled tetap masuk DB (opsional, tapi aman).
     * Kalau fillable model Anda sudah benar, ini sebenarnya tidak wajib,
     * tapi saya biarkan supaya sesuai pola Anda.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            'tanggal_pembayaran'  => $data['tanggal_pembayaran'] ?? now(),
            'faktur_pembelian_id' => $data['faktur_pembelian_id'] ?? null,
            'vendor_id'           => $data['vendor_id'] ?? null,
            'bank'                => $data['bank'] ?? '-',
            'no_rekening'         => $data['no_rekening'] ?? '-',
            'nilai_pembayaran'    => (float) ($data['nilai_pembayaran'] ?? 0),
        ];
    }

    /**
     * ✅ Setelah pembayaran berhasil dibuat:
     * - update status PO menjadi LUNAS
     * - tampilkan notifikasi
     */
    protected function afterCreate(): void
    {
        $fakturId = $this->record?->faktur_pembelian_id;

        if (! $fakturId) {
            return;
        }

        $faktur = FakturPembelian::with('pembelian')->find($fakturId);

        if ($faktur?->pembelian) {
            Pembelian::whereKey($faktur->pembelian->id)->update([
                'status' => 'lunas',
            ]);
        }


        Notification::make()
            ->title('Pembayaran berhasil disimpan')
            ->body('Status pesanan pembelian otomatis berubah menjadi LUNAS.')
            ->success()
            ->send();
    }

    /**
     * ✅ Redirect balik ke Detail Pembelian (flow 1 halaman)
     */
    protected function getRedirectUrl(): string
    {
        $fakturId = $this->record?->faktur_pembelian_id;

        if (! $fakturId) {
            return static::getResource()::getUrl('index');
        }

        $faktur = FakturPembelian::with('pembelian')->find($fakturId);

        if ($faktur?->pembelian) {
            return PembelianResource::getUrl('view', ['record' => $faktur->pembelian->id]);
        }

        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Simpan'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
