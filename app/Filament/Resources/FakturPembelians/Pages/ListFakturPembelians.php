<?php

namespace App\Filament\Resources\FakturPembelians\Pages;

use App\Filament\Resources\FakturPembelians\FakturPembelianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFakturPembelians extends ListRecords
{
    protected static string $resource = FakturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah'),
        ];
    }
}
