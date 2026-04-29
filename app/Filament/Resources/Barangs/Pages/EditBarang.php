<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Barangs\BarangResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBarang extends EditRecord
{
    use HasBackButtonHeading;


    protected static string $resource = BarangResource::class;

    /**
     * Tombol di header (kanan atas)
     */
    protected function getHeaderActions(): array
    {
        return [
            
        ];
    }

    /**
     * 🔥 SETELAH SIMPAN → KEMBALI KE DAFTAR BARANG
     */
    protected function getRedirectUrl(): string
    {
        return BarangResource::getUrl('index');
    }
}
