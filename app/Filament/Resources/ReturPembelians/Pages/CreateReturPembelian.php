<?php

namespace App\Filament\Resources\ReturPembelians\Pages;

use App\Filament\Resources\ReturPembelians\ReturPembelianResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateReturPembelian extends CreateRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan')
            ->disabled(fn (): bool => $this->hasInvalidReturForm());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dibuat_oleh'] = auth()->id();
        $data['status'] = 'menunggu';

        return $data;
    }

    private function hasInvalidReturForm(): bool
    {
        $data = $this->data ?? [];

        if (! ReturPembelianResource::poHasConfirmedGrn($data['pembelian_id'] ?? null)) {
            return true;
        }

        $details = $data['details'] ?? [];

        if (empty($details)) {
            return true;
        }

        foreach ($details as $detail) {
            $qtyRetur = (int) ($detail['qty_retur'] ?? 0);
            $qtyDiterima = (int) ($detail['qty_diterima_display'] ?? 0);

            if ($qtyRetur <= 0 || ($qtyDiterima > 0 && $qtyRetur > $qtyDiterima)) {
                return true;
            }
        }

        return false;
    }
}
