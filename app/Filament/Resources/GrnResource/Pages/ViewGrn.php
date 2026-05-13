<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use App\Filament\Traits\HasBackButtonHeading;
use App\Models\Grn;
use App\Services\GrnKonfirmasiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewGrn extends ViewRecord
{
    use HasBackButtonHeading;

    protected static string $resource = GrnResource::class;

    public function getTitle(): string
    {
        /** @var Grn $record */
        $record = $this->getRecord();

        return 'Detail GRN: ' . $record->nomor_grn;
    }

    public function getRecord(): Model
    {
        return parent::getRecord()->load([
            'pembelian.details.barang',
            'vendor',
            'details.barang',
            'details.pembelianDetail',
        ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var Grn $grn */
        $grn = $this->getRecord();

        return [
            Action::make('konfirmasi')
                ->label('Konfirmasi Terima')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible($grn->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penerimaan Barang')
                ->modalDescription("Apakah Anda yakin ingin mengkonfirmasi penerimaan {$grn->nomor_grn}?\nStok barang akan otomatis bertambah setelah dikonfirmasi.")
                ->modalCancelActionLabel('Batal')
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->action(function () use ($grn) {
                    try {
                        app(GrnKonfirmasiService::class)->konfirmasi($grn, auth()->id() ?? 1);

                        Notification::make()
                            ->title($grn->nomor_grn . ' berhasil dikonfirmasi.')
                            ->body('Stok barang telah diperbarui.')
                            ->success()
                            ->send();

                        return redirect(GrnResource::getUrl('index'));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Konfirmasi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('edit')
                ->label('Edit GRN')
                ->color('gray')
                ->icon('heroicon-o-pencil')
                ->visible($grn->status === 'draft')
                ->url(fn () => GrnResource::getUrl('edit', ['record' => $grn->id])),
        ];
    }
}
