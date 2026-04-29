<?php

namespace App\Filament\Resources\Jurnals\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Jurnals\JurnalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditJurnal extends EditRecord
{
    use HasBackButtonHeading;

    protected static string $resource = JurnalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterValidation(): void
    {
        $data = $this->form->getState();
        
        $totalDebit = collect($data['details'] ?? [])->sum('debit');
        $totalKredit = collect($data['details'] ?? [])->sum('kredit');

        if ($totalDebit !== $totalKredit) {
            Notification::make()
                ->danger()
                ->title('Jurnal Tidak Balance')
                ->body("Total Debit (Rp " . number_format($totalDebit, 0, ',', '.') . ") tidak sama dengan Total Kredit (Rp " . number_format($totalKredit, 0, ',', '.') . ").")
                ->send();

            throw ValidationException::withMessages([
                'data.details' => 'Total Debit dan Total Kredit harus seimbang.',
            ]);
        }
    }
}
