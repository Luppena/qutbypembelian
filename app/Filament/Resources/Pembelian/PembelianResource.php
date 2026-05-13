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
use Filament\Support\Icons\Heroicon;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Traits\HasRoleAccess;

class PembelianResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['admin', 'operasional'];
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
                            ->label('Tanggal PO')
                            ->required()
                            ->default(now()),

                        TextInput::make('nomor')
                            ->label('Nomor PO')
                            ->default(fn () => Pembelian::generateNomorPembelian())
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
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $vendor = Vendor::find($state);
                                $diskon = $vendor?->diskon_persen ?? 0;

                                // Isi diskon otomatis dari vendor
                                $set('diskon', $diskon);

                                // Hitung ulang total
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
                                        self::hitungSubtotal($get, $set);
                                    }),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::hitungSubtotal($get, $set)),

                                TextInput::make('satuan')
                                    ->label('Satuan')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),


                                TextInput::make('harga')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::hitungSubtotal($get, $set)),

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
                            ->dehydrated()
                            ->live(),

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

    /**
     * Hitung ulang Total, Diskon, PPN, dan Total Akhir.
     * $prefix = '' jika dipanggil dari form root (vendor, ppn checkbox)
     * $prefix = '../../' jika dipanggil dari dalam repeater item
     */
    protected static function recalculate(Get $get, Set $set, string $prefix = ''): void
    {
        // 1. Total = jumlah semua subtotal baris
        $details = $get($prefix . 'details') ?? [];
        $total = 0;

        foreach ($details as $item) {
            $qty   = (float) ($item['qty'] ?? 0);
            $harga = (float) ($item['harga'] ?? 0);
            $total += $qty * $harga;
        }

        // 2. Diskon (Rp) = Total × (Diskon % / 100)
        $diskonPersen  = (float) ($get($prefix . 'diskon') ?? 0);
        $diskonRp      = $total * ($diskonPersen / 100);

        // 3. Setelah Diskon = Total − Diskon (Rp)
        $setelahDiskon = max(0, $total - $diskonRp);

        // 4. PPN (Rp) = Setelah Diskon × 11% (jika aktif)
        $ppnAktif = (bool) ($get($prefix . 'ppn') ?? false);
        $ppnRp    = $ppnAktif ? $setelahDiskon * 0.11 : 0;

        // 5. Total Akhir = Setelah Diskon + PPN (Rp)
        $totalAkhir = $setelahDiskon + $ppnRp;

        $set($prefix . 'total', round($total));
        $set($prefix . 'total_akhir', round($totalAkhir));
    }

    /** Dipanggil dari form root (vendor, ppn) */
    protected static function hitungTotal(Get $get, Set $set): void
    {
        self::recalculate($get, $set, '');
    }

    /** Dipanggil dari dalam repeater item (barang, qty, harga) */
    protected static function hitungSubtotal(Get $get, Set $set): void
    {
        $qty     = (float) ($get('qty') ?? 0);
        $harga   = (float) ($get('harga') ?? 0);
        $subtotal = $qty * $harga;

        $set('subtotal', round($subtotal));

        // Navigate up dari repeater scope ke form root
        self::recalculate($get, $set, '../../');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal PO')->date()->sortable(),
                TextColumn::make('nomor')->label('No. PO')->searchable(),
                TextColumn::make('vendor.nama_vendor')->label('Vendor')->searchable(),
                TextColumn::make('total_akhir')
                    ->label('Total PO')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'menunggu',
                        'warning' => 'partial',
                        'success' => 'selesai',
                        'danger' => 'dibatalkan',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'menunggu' => 'Menunggu',
                        'partial' => 'Partial',
                        'selesai' => 'Selesai',
                        'dikirim' => 'Dikirim',
                        'sebagian' => 'Sebagian',
                        'diterima' => 'Diterima',
                        default => ucfirst((string) $state),
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),

                EditAction::make()
                    ->label('Edit')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'menunggu'], true)),

                DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'menunggu'], true))
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
