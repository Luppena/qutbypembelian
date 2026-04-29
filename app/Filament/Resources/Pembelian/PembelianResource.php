<?php

namespace App\Filament\Resources\Pembelian;

use App\Filament\Resources\Pembelian\Pages;
use App\Models\Pembelian;
use App\Models\Barang;
use App\Models\Vendor;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Traits\HasRoleAccess;

class PembelianResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static ?string $model = Pembelian::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi Pembelian';

    // ✅ FIX: ganti label menu utama
    protected static ?string $navigationLabel = 'Pesanan Pembelian';

    // (opsional) rapikan label plural
    protected static ?string $pluralModelLabel = 'Daftar Pesanan Pembelian';

    // (opsional) urutan menu
    protected static ?int $navigationSort = 1;

    /* =========================
     * FORM
     * ========================= */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Informasi Pembelian')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('tanggal')
                            ->required()
                            ->default(now()),

                        TextInput::make('nomor')
                            ->label('Nomor Pembelian')
                            ->default(function () {
                                $last = Pembelian::latest('id')->first();
                                $lastNumber = $last ? (int) substr($last->nomor, -4) : 0;
                                return 'PB-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                            })
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'nama_vendor')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if (! $state) {
                                    $set('diskon', 0);
                                    return;
                                }

                                $vendor = Vendor::find($state);
                                $set('diskon', (float) ($vendor->diskon_persen ?? 0));

                                self::hitungTotal($get, $set);
                            }),
                    ]),

                Section::make('Rincian Pembelian Barang')
                    ->schema([
                        Repeater::make('details')
                            ->label('Rincian Barang')
                            ->relationship('details')
                            ->addActionLabel('Tambah Barang')
                            ->columns(5)
                            ->schema([
                                Select::make('barang_id')
                                    ->label('Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $barang = Barang::find($state);
                                        if (! $barang) return;

                                        $qty   = (int) ($get('qty') ?? 1);
                                        $harga = (float) $barang->harga_barang;

                                        $set('satuan', $barang->satuan);
                                        $set('harga', $harga);
                                        $set('subtotal', $qty * $harga);

                                        self::hitungTotal($get, $set);
                                    }),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $harga = (float) ($get('harga') ?? 0);
                                        $set('subtotal', ((int) $state) * $harga);

                                        self::hitungTotal($get, $set);
                                    }),

                                Select::make('satuan')
                                    ->label('Satuan')
                                    ->options([
                                        'pcs'   => 'pcs',
                                        'set'   => 'set',
                                        'lusin' => 'lusin',
                                    ])
                                    ->required(),

                                TextInput::make('harga')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $qty = (int) ($get('qty') ?? 1);
                                        $set('subtotal', $qty * (float) $state);

                                        self::hitungTotal($get, $set);
                                    }),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->minItems(1),
                    ]),

                Section::make('Perhitungan Total')
                    ->columns(4)
                    ->schema([
                        TextInput::make('total')
                            ->label('Total')
                            ->prefix('Rp ')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('diskon')
                            ->label('Diskon')
                            ->suffix('%')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->live()
                            ->dehydrated(),

                        Checkbox::make('ppn')
                            ->label('PPN 11%')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::hitungTotal($get, $set)),

                        TextInput::make('total_akhir')
                            ->label('Total Akhir')
                            ->prefix('Rp ')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    protected static function hitungTotal(Get $get, Set $set): void
    {
        $details = $get('details') ?? [];

        $total = collect($details)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

        $diskonPersen  = (float) ($get('diskon') ?? 0);
        $diskonNominal = $total * ($diskonPersen / 100);

        $dpp = max($total - $diskonNominal, 0);

        $ppnAktif = (bool) ($get('ppn') ?? false);
        $ppn      = $ppnAktif ? $dpp * 0.11 : 0;

        $totalAkhir = $dpp + $ppn;

        $set('total', round($total));
        $set('total_akhir', round($totalAkhir));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('status = "lunas" ASC')->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal Pembelian')->date()->sortable(),
                TextColumn::make('nomor')->label('Nomor Pembelian'),
                TextColumn::make('vendor.nama_vendor')->label('Vendor'),
                TextColumn::make('total_akhir')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'lunas' ? 'Lunas' : 'Belum Lunas')
                    ->color(fn ($state) => $state === 'lunas' ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),

                EditAction::make()
                    ->label('Edit')
                    ->visible(fn ($record) => $record->status !== 'lunas'),

                DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($record) => $record->status !== 'lunas')
                    ->modalHeading('Hapus Pembelian')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data pembelian ini?')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPembelian::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'edit'   => Pages\EditPembelian::route('/{record}/edit'),
            'view'   => Pages\ViewPembelian::route('/{record}'),
        ];
    }
}