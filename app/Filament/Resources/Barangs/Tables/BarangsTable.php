<?php

namespace App\Filament\Resources\Barangs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BarangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_barang')
                    ->label('Kode Barang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori Barang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable(),

                
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),   
                EditAction::make()
                    ->label('Edit'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Vendor')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data vendor ini?')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->toolbarActions([
            
            ]);
    }
}
