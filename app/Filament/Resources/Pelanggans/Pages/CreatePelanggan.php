<?php

namespace App\Filament\Resources\Pelanggans\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Pelanggans\PelangganResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePelanggan extends CreateRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PelangganResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
