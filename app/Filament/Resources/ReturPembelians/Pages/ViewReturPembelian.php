<?php

namespace App\Filament\Resources\ReturPembelians\Pages;

use App\Filament\Resources\ReturPembelians\ReturPembelianResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewReturPembelian extends ViewRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->visible(fn () => $this->getRecord()->status === 'draft'),
        ];
    }
}
