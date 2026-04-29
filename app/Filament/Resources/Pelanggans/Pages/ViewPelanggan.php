<?php

namespace App\Filament\Resources\Pelanggans\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Pelanggans\PelangganResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPelanggan extends ViewRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PelangganResource::class;
}
