<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Resources\Pages\ListRecords;

class ListBarangMasuk extends ListRecords
{
    protected static string $resource = BarangMasukResource::class;

    public function getTitle(): string
    {
        return 'Daftar Barang Masuk';
    }

    protected function getHeaderActions(): array
    {
        return []; // Gudang tidak bisa buat PO
    }
}
