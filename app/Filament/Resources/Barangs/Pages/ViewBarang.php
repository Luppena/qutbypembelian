<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Resources\Barangs\BarangResource;
use App\Filament\Traits\HasBackButtonHeading;
use Filament\Resources\Pages\ViewRecord;

class ViewBarang extends ViewRecord
{
    use HasBackButtonHeading;

    protected static string $resource = BarangResource::class;
}
