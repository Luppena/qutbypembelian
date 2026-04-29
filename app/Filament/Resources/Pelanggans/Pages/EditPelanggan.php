<?php

namespace App\Filament\Resources\Pelanggans\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Pelanggans\PelangganResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPelanggan extends EditRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PelangganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
