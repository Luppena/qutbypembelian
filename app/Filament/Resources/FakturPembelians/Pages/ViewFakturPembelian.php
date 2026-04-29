<?php

namespace App\Filament\Resources\FakturPembelians\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\FakturPembelians\FakturPembelianResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFakturPembelian extends ViewRecord
{
    use HasBackButtonHeading;


    protected static string $resource = FakturPembelianResource::class;

    public function getTitle(): string
    {
        return 'Detail Pembayaran';
    }

}
