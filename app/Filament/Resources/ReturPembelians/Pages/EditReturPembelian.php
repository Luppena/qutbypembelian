<?php

namespace App\Filament\Resources\ReturPembelians\Pages;

use App\Filament\Resources\ReturPembelians\ReturPembelianResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditReturPembelian extends EditRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
