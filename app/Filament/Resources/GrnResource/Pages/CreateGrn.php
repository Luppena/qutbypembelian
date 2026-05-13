<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use App\Models\Grn;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateGrn extends CreateRecord
{
    protected static string $resource = GrnResource::class;

    public function getTitle(): string
    {
        return 'Form Penerimaan Barang (GRN)';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['nomor_grn'] = Grn::generateNomor();

        // Pastikan details disiapkan dari PO jika belum ada
        if (empty($data['details']) && ! empty($data['pembelian_id'])) {
            $po = Pembelian::with('details.barang')->find($data['pembelian_id']);
            if ($po) {
                $data['details'] = GrnResource::getOpenGrnItems($po);
            }
        }

        if (empty($data['details'])) {
            throw ValidationException::withMessages([
                'pembelian_id' => 'Semua item PO ini sudah terpenuhi. Pilih PO lain yang masih memiliki outstanding.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return GrnResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->submit('create'),

            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
