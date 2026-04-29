<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Penjualans\PenjualanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjualan extends CreateRecord
{
    use HasBackButtonHeading;


    protected static string $resource = PenjualanResource::class;

    /**
     * Hitung ulang total sebelum data disimpan
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $totalBruto = 0;

        foreach ($data['detail'] ?? [] as $row) {
            $totalBruto += (float) ($row['subtotal'] ?? 0);
        }

        $diskonPersen = (float) ($data['diskon_persen'] ?? 0);
        $diskonRp     = $totalBruto * ($diskonPersen / 100);
        $totalNetto   = $totalBruto - $diskonRp;

        // set ke data penjualan
        $data['total_bruto'] = round($totalBruto);
        $data['diskon_rp']   = round($diskonRp);
        $data['total_netto'] = round($totalNetto);

        return $data;
    }
}
