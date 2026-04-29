<?php

namespace App\Filament\Resources\FakturPembelians\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\FakturPembelians\FakturPembelianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFakturPembelian extends EditRecord
{
    use HasBackButtonHeading;


    protected static string $resource = FakturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
