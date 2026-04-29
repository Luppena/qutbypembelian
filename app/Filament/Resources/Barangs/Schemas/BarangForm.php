<?php

namespace App\Filament\Resources\Barangs\Schemas;

use App\Models\Barang;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class BarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Barang')
                    ->schema([
                        TextInput::make('kode_barang')
                ->label('Kode Barang')
                ->default(fn () => Barang::getKodeBarang())
                ->disabled()
                ->dehydrated()
                ->required(),

                        TextInput::make('nama_barang')
                            ->label('Nama barang')
                            ->required(),
                        Select::make('kategori')
                            ->label('Kategori Barang')
                            ->options([
                                'Atasan'     => 'Atasan',
                                'Bawahan'    => 'Bawahan',
                                'Dress'      => 'Dress / Gamis',
                                'Setelan'    => 'Setelan',
                               'Outerwear'  => 'Outerwear',
                               'Aksesoris'  => 'Aksesoris'

                               ,
                             ])
                             ->required(),
                        Select::make('satuan')
                                ->label('Satuan')
                                ->options([
                                  'pcs'   => 'Pcs',
                                 'set'   => 'Set',
                                 'lusin' => 'Lusin',
                                ])
                                ->default(1)
                                ->required(),

                        TextInput::make('stok')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Harga')
                    ->schema([
                        TextInput::make('hpp_satuan')
                            ->label('HPP satuan')
                            ->required()
                            ->numeric(),

                        TextInput::make('harga_barang')
                           ->label('Harga Jual')
                           ->numeric()
                           ->prefix('Rp ')
                           ->required(),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }
}