<?php

namespace App\Filament\Resources\PembayaranPembelians\Pages;

use App\Filament\Resources\PembayaranPembelians\PembayaranPembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPembayaranPembelians extends ListRecords
{
    protected static string $resource = PembayaranPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah'), // 🔥 GANTI TEKS TOMBOL
        ];
    }
}
