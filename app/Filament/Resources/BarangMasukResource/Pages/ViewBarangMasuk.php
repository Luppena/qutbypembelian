<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use App\Filament\Resources\GrnResource;
use App\Filament\Traits\HasBackButtonHeading;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewBarangMasuk extends ViewRecord
{
    use HasBackButtonHeading;

    protected static string $resource = BarangMasukResource::class;

    public function getTitle(): string
    {
        /** @var Pembelian $record */
        $record = $this->getRecord();
        return 'Detail PO: ' . $record->nomor;
    }

    protected function getHeaderActions(): array
    {
        /** @var Pembelian $record */
        $record = $this->getRecord()->load(['grns', 'details']);

        $masihAdaOutstanding = $record->details->contains(fn ($detail) => (int) $detail->qty_outstanding > 0);

        return [
            Action::make('buat_grn')
                ->label('Buat Penerimaan Barang')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->visible($masihAdaOutstanding)
                ->url(fn () => GrnResource::getUrl('create') . '?pembelian_id=' . $record->id),

            Action::make('lihat_grn')
                ->label('Lihat Penerimaan')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible($record->grns->isNotEmpty())
                ->url(fn () => GrnResource::getUrl('index') . '?pembelian_id=' . $record->id),
        ];
    }
}
