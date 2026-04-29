<?php

namespace App\Filament\Resources\Pelanggans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PelanggansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pelanggan')
                    ->label('Kode pelanggan')
                    ->sortable(),

                TextColumn::make('nama_pelanggan')
                    ->label('Nama pelanggan')
                    ->searchable(),

                TextColumn::make('no_telp')
                    ->label('No. Telepon'),

                TextColumn::make('alamat')
                    ->label('Alamat'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }
}
