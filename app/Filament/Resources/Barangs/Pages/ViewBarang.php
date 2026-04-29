<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Traits\HasBackButtonHeading;
use App\Filament\Resources\Barangs\BarangResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBarang extends ViewRecord
{
    use HasBackButtonHeading;

    protected static string $resource = BarangResource::class;

    public function getTitle(): string
    {
        return 'Lihat ' . $this->record->nama_barang;
    }
}
