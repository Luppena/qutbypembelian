<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Penjualans\PenjualanResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjualan extends ViewRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PenjualanResource::class;

    // 🔥 INI KUNCINYA
    protected function isFormDisabled(): bool
    {
        return false;
    }
}
