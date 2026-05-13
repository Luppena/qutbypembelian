<?php

namespace App\Filament\Resources\Barangs\Schemas;

use App\Models\Barang;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Barang')
                    ->schema([
                        TextInput::make('kode_barang')
                            ->label('Kode barang')
                            ->required()
                            ->readOnly()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->afterStateHydrated(function (TextInput $component, $state) {
                                if (blank($state)) {
                                    $component->state(Barang::generateNextKodeBarang());
                                }
                            }),

                        TextInput::make('nama_barang')
                            ->label('Nama barang')
                            ->required(),

                        Select::make('satuan')
                            ->label('Satuan')
                            ->options([
                                'Pcs'   => 'Pcs',
                                'Lusin' => 'Lusin',
                                'Set'   => 'Set',
                            ])
                            ->searchable()
                            ->placeholder('Pilih satuan'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Hidden::make('stok')->default(0),
                Hidden::make('harga_beli')->default(0),
                Hidden::make('harga_barang')->default(0),
            ]);
    }
}
