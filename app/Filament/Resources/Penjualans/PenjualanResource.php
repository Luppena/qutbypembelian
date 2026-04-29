<?php

namespace App\Filament\Resources\Penjualans;

use App\Filament\Resources\Penjualans\Pages\{
    CreatePenjualan,
    EditPenjualan,
    ListPenjualans,
    ViewPenjualan
};
use App\Models\Barang;
use App\Models\Penjualan;
use BackedEnum;
use UnitEnum;
use Filament\Actions;
use Filament\Forms\Components\{
    DatePicker,
    Repeater,
    Select,
    TextInput
};
use Filament\Resources\Resource;
use Filament\Schemas\Components\{
    Grid,
    Section
};
use Filament\Schemas\Components\Utilities\{
    Get,
    Set
};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Traits\HasRoleAccess;

class PenjualanResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static ?string $model = Penjualan::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Penjualan';

    /* =====================================================
     * GENERATE NO FAKTUR OTOMATIS
     * ===================================================== */
    protected static function generateNoFaktur(): string
    {
        $tahun = now()->year;

        $last = Penjualan::whereYear('created_at', $tahun)
            ->where('no_faktur', 'like', "FJ-$tahun-%")
            ->orderByDesc('no_faktur')
            ->first();

        $nomor = $last
            ? ((int) substr($last->no_faktur, -4)) + 1
            : 1;

        return 'FJ-' . $tahun . '-' . str_pad($nomor, 4, '0', STR_PAD_LEFT);
    }

    /* =====================================================
     * FORM
     * ===================================================== */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->live() // 🔥 WAJIB agar hitungan muncul
            ->columns(1)
            ->components([

                /* ========= INFORMASI FAKTUR ========= */
                Section::make('Informasi Faktur')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('tanggal_faktur')
                            ->label('Tanggal Faktur')
                            ->required(),

                        TextInput::make('no_faktur')
                            ->label('No Faktur')
                            ->default(fn () => static::generateNoFaktur())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Select::make('pelanggan_id')
                            ->label('Pelanggan')
                            ->relationship('pelanggan', 'nama_pelanggan')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                /* ========= DETAIL PENJUALAN ========= */
                Section::make('Detail Penjualan')
                    ->schema([
                        Repeater::make('detail')
                            ->relationship('detail')
                            ->live() // 🔥 KUNCI
                            ->columns(4)
                            ->defaultItems(1)
                            ->schema([

                                Select::make('barang_id')
                                    ->label('Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if (! $state) return;

                                        $barang = Barang::find($state);
                                        if (! $barang) return;

                                        $qty = (int) ($get('qty') ?? 1);

                                        $set('harga_satuan', $barang->harga_barang);
                                        $set('subtotal', $barang->harga_barang * $qty);

                                        static::hitungTotal($get, $set);
                                    }),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $harga = (float) ($get('harga_satuan') ?? 0);
                                        $qty   = (int) $state;

                                        $set('subtotal', $harga * $qty);
                                        static::hitungTotal($get, $set);
                                    }),

                                TextInput::make('harga_satuan')
                                    ->label('Harga Jual / Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->reactive()
                                    ->dehydrated(),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->reactive()
                                    ->dehydrated(),
                            ]),
                    ]),

                /* ========= RINGKASAN ========= */
                Section::make('Ringkasan')
                    ->schema([
                        TextInput::make('total_bruto')
                            ->label('Total Bruto')
                            ->prefix('Rp')
                            ->disabled()
                            ->reactive()
                            ->dehydrated(),

                        Grid::make(2)->schema([
                            TextInput::make('diskon_persen')
                                ->label('Diskon (%)')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Get $get, Set $set) =>
                                    static::hitungTotal($get, $set)
                                )
                                ->dehydrated(),

                            TextInput::make('diskon_rp')
                                ->label('Diskon (Rp)')
                                ->prefix('Rp')
                                ->disabled()
                                ->reactive()
                                ->dehydrated(),
                        ]),

                        TextInput::make('total_netto')
                            ->label('Total Netto')
                            ->prefix('Rp')
                            ->disabled()
                            ->reactive()
                            ->dehydrated(),
                    ]),
            ]);
    }

    /* =====================================================
     * HITUNG TOTAL
     * ===================================================== */
    protected static function hitungTotal(Get $get, Set $set): void
    {
        $detail = $get('detail') ?? [];

        // Total Bruto = jumlah semua subtotal
        $totalBruto = collect($detail)->sum(
            fn ($item) => (float) ($item['subtotal'] ?? 0)
        );

        // Diskon
        $diskonPersen = (float) ($get('diskon_persen') ?? 0);
        $diskonRp     = $totalBruto * ($diskonPersen / 100);

        // Total Netto
        $totalNetto = max($totalBruto - $diskonRp, 0);

        $set('total_bruto', round($totalBruto));
        $set('diskon_rp', round($diskonRp));
        $set('total_netto', round($totalNetto));
    }

    /* =====================================================
     * TABLE
     * ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_faktur')->date(),
                TextColumn::make('no_faktur')->label('No Faktur'),
                TextColumn::make('pelanggan.nama_pelanggan')->label('Pelanggan'),
                TextColumn::make('total_netto')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) =>
                        'Rp ' . number_format($state ?? 0, 0, ',', '.')
                    ),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    /* =====================================================
     * PAGES
     * ===================================================== */
    public static function getPages(): array
    {
        return [
            'index'  => ListPenjualans::route('/'),
            'create' => CreatePenjualan::route('/create'),
            'view'   => ViewPenjualan::route('/{record}'),
            'edit'   => EditPenjualan::route('/{record}/edit'),
        ];
    }
}
