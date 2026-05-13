<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use Filament\Resources\Pages\EditRecord;

class EditGrn extends EditRecord
{
    protected static string $resource = GrnResource::class;

    public function getTitle(): string
    {
        return 'Edit GRN: ' . $this->getRecord()->nomor_grn;
    }

    protected function getRedirectUrl(): string
    {
        return GrnResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
