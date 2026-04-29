<?php

namespace App\Filament\Resources\PembayaranPembelians\Schemas;

use App\Models\FakturPembelian;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PembayaranPembelianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembayaran Pembelian')
                ->columns(4)
                ->columnSpanFull()
                ->schema([

                    DatePicker::make('tanggal_pembayaran')
                        ->label('Tanggal Pembayaran')
                        ->default(now())
                        ->required(),

                    Select::make('faktur_pembelian_id')
                        ->label('Faktur Pembelian')
                        ->relationship('fakturPembelian', 'nomor_faktur_vendor')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nomor_faktur_vendor ?? '-')
                        ->searchable()
                        ->preload()
                        ->required()
                        // ✅ Step 3 kirim: ?faktur_id=xx
                        ->default(fn () => request()->get('faktur_id'))
                        ->live()
                        // ✅ saat pertama kali buka form, langsung isi otomatis kalau faktur_id ada
                        ->afterStateHydrated(function ($state, Get $get, Set $set) {
                            if (! $state) {
                                return;
                            }

                            $faktur = FakturPembelian::with('vendor')->find($state);

                            if (! $faktur) {
                                $set('nilai_pembayaran', 0);
                                $set('vendor_id', null);
                                $set('bank', null);
                                $set('no_rekening', null);
                                return;
                            }

                            $set('nilai_pembayaran', $faktur->total_netto ?? 0);
                            $set('vendor_id', $faktur->vendor_id);

                            $vendor = $faktur->vendor;
                            $set('bank', $vendor?->nama_bank ?? '-');
                            $set('no_rekening', $vendor?->nomor_rekening ?? '-');
                        })
                        // ✅ kalau user ganti faktur, isi ulang
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (! $state) {
                                $set('nilai_pembayaran', 0);
                                $set('vendor_id', null);
                                $set('bank', null);
                                $set('no_rekening', null);
                                return;
                            }

                            $faktur = FakturPembelian::with('vendor')->find($state);

                            if (! $faktur) {
                                $set('nilai_pembayaran', 0);
                                $set('vendor_id', null);
                                $set('bank', null);
                                $set('no_rekening', null);
                                return;
                            }

                            $set('nilai_pembayaran', $faktur->total_netto ?? 0);
                            $set('vendor_id', $faktur->vendor_id);

                            $vendor = $faktur->vendor;
                            $set('bank', $vendor?->nama_bank ?? '-');
                            $set('no_rekening', $vendor?->nomor_rekening ?? '-');
                        }),

                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->relationship('vendor', 'nama_vendor')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nama_vendor ?? '-')
                        ->disabled()
                        ->dehydrated(true)
                        ->required(),

                    TextInput::make('bank')
                        ->label('Bank')
                        ->disabled()
                        ->dehydrated(true)
                        ->required(),

                    TextInput::make('no_rekening')
                        ->label('No Rekening')
                        ->disabled()
                        ->dehydrated(true)
                        ->required(),

                    TextInput::make('nilai_pembayaran')
                        ->label('Nilai Pembayaran')
                        ->disabled()
                        ->dehydrated(true)
                        ->numeric()
                        ->prefix('Rp ')
                        ->required(),
                ]),
        ]);
    }
}