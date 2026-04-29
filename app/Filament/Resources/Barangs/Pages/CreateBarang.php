<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Traits\HasBackButtonHeading;

use App\Filament\Resources\Barangs\BarangResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateBarang extends CreateRecord
{
    use HasBackButtonHeading;


    protected static string $resource = BarangResource::class;

    /**
     * Judul halaman
     */
    public function getTitle(): string
    {
        return 'Tambah Data Barang';
    }

    /**
     * 🔥 GANTI TOTAL ACTION FORM
     * - Buat → Simpan
     * - Hilangkan "Buat & buat lainnya"
     */
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
