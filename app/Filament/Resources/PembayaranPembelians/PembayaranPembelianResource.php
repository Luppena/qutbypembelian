<?php

namespace App\Filament\Resources\PembayaranPembelians;

use App\Filament\Resources\PembayaranPembelians\Pages;
use App\Models\FakturPembelian;
use App\Models\PembayaranPembelian;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use App\Filament\Traits\HasRoleAccess;

class PembayaranPembelianResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static ?string $model = PembayaranPembelian::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Pembayaran Pembelian';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
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
                        ->relationship(
                            name: 'fakturPembelian',
                            titleAttribute: 'nomor_faktur_vendor',
                            // ✅ hanya faktur yang belum punya pembayaran
                            modifyQueryUsing: fn ($query) => $query->whereDoesntHave('pembayarans')
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nomor_faktur_vendor ?? '-')
                        ->searchable()
                        ->preload()
                        ->required()
                        // ✅ support Step 3: ?faktur_id=xx
                        ->default(fn () => request()->get('faktur_id'))
                        ->live()
                        ->hiddenOn('view')
                        // ✅ saat pertama kali buka form, auto isi jika faktur_id sudah ada
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
                        // ✅ kalau user ganti faktur manual, isi ulang
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

                    // tampil khusus saat view
                    TextInput::make('faktur_nomor')
                        ->label('Faktur Pembelian')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('view')
                        ->formatStateUsing(fn ($record) => $record?->fakturPembelian?->nomor_faktur_vendor ?? '-'),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pembayaran')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor'),

                TextColumn::make('bank')
                    ->label('Bank'),

                TextColumn::make('nilai_pembayaran')
                    ->label('Nilai Pembayaran')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tanggal_pembayaran')
                    ->form([
                        DatePicker::make('start')->label('Dari'),
                        DatePicker::make('end')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['start'] ?? null,
                                fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '>=', $date)
                            )
                            ->when(
                                $data['end'] ?? null,
                                fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '<=', $date)
                            );
                    }),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPembayaranPembelians::route('/'),
            'create' => Pages\CreatePembayaranPembelian::route('/create'),
            'view'   => Pages\ViewPembayaranPembelian::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}