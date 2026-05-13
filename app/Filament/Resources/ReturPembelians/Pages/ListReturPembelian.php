<?php

namespace App\Filament\Resources\ReturPembelians\Pages;

use App\Filament\Resources\ReturPembelians\ReturPembelianResource;
use Filament\Resources\Pages\ListRecords;

class ListReturPembelian extends ListRecords
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->label('Buat Retur Baru'),
        ];
    }
}
