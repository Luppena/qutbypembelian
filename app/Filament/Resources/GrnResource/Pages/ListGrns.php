<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use Filament\Resources\Pages\ListRecords;

class ListGrns extends ListRecords
{
    protected static string $resource = GrnResource::class;

    public function getTitle(): string
    {
        return 'Daftar Penerimaan Barang';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->label('Buat Penerimaan Barang'),
        ];
    }
}
