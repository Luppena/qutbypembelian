<?php

namespace App\Filament\Resources\Pembelian\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

// ⬇️ ACTIONS
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->label('Kode Pembelian')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal Pemesanan')
                    ->date()
                    ->sortable(),

                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor')
                    ->searchable(),

                TextColumn::make('total_akhir')
                    ->label('Total Pembelian')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'proses',
                        'success' => 'diterima',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('Lihat'),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
