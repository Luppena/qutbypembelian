<?php

namespace App\Filament\Resources\FakturPembelians;

use App\Filament\Resources\FakturPembelians\Pages\CreateFakturPembelian;
use App\Filament\Resources\FakturPembelians\Pages\ListFakturPembelians;
use App\Filament\Resources\FakturPembelians\Pages;
use App\Models\FakturPembelian;
use App\Models\Pembelian;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;
use App\Filament\Traits\HasRoleAccess;

class FakturPembelianResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static ?string $model = FakturPembelian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Pembayaran Pembelian';
    protected static ?string $modelLabel = 'Pembayaran Pembelian';
    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';
    protected static ?string $recordTitleAttribute = 'nomor_faktur_vendor';
    protected static ?int $navigationSort = 3;

    /**
     * Helper: isi Vendor, Details, dan Total dari Pembelian (PO)
     */
    protected static function fillFromPembelian($state, callable $set): void
    {
        if (! $state) {
            $set('vendor_id', null);
            $set('total_bruto', 0);
            $set('diskon_persen', 0);
            $set('total_netto', 0);
            return;
        }

        $pembelian = Pembelian::with('details.barang', 'vendor')->find($state);
        if (! $pembelian) {
            $set('vendor_id', null);
            $set('total_bruto', 0);
            $set('diskon_persen', 0);
            $set('total_netto', 0);
            return;
        }

        // vendor
        $set('vendor_id', $pembelian->vendor_id);

        // ✅ auto-fill dropdown bank dan nomor rekening dari vendor asalkan ada nilainya
        if ($pembelian->vendor) {
            $set('bank', $pembelian->vendor->nama_bank ?? 'BCA');
            $set('no_rekening', $pembelian->vendor->nomor_rekening ?? '-');
        }

        // ✅ FIX: harga di PembelianDetail Anda umumnya "harga_satuan", bukan "harga"
        $details = $pembelian->details->map(fn ($item) => [
            'barang_id'   => $item->barang_id,
            'nama_barang' => $item->barang?->nama_barang ?? '-',
            'qty'         => (int) $item->qty,
            'harga'       => (float) ($item->harga_satuan ?? $item->harga ?? 0),
            'subtotal'    => (float) ($item->subtotal ?? ((int) $item->qty) * (float) ($item->harga_satuan ?? $item->harga ?? 0)),
        ])->toArray();

        // total
        $total = collect($details)->sum(fn ($d) => (float) ($d['subtotal'] ?? 0));

        // diskon dari pembelian (pakai field Anda 'diskon')
        $diskonPersen = (float) ($pembelian->diskon ?? 0);
        $nilaiDiskon  = ($diskonPersen / 100) * $total;
        $totalNetto   = max($total - $nilaiDiskon, 0);

        $set('total_bruto', $total);
        $set('diskon_persen', $diskonPersen);
        $set('total_netto', $totalNetto);
    }

    /* =====================================================
     | FORM
     ===================================================== */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        DatePicker::make('tanggal_faktur')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),

                        TextInput::make('nomor_faktur_vendor')
                            ->label('Nomor Faktur Vendor')
                            ->required(),

                        Select::make('pembelian_id')
                            ->label('Nomor Pembelian')
                            ->relationship(
                                name: 'pembelian',
                                titleAttribute: 'nomor',
                                modifyQueryUsing: fn ($query, $livewire) =>
                                    $livewire instanceof CreateRecord
                                        ? $query
                                            ->where('status', '!=', 'lunas')
                                            ->whereDoesntHave('fakturPembelian')
                                        : $query
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            // ✅ support Step 2: ?pembelian_id=xx
                            ->default(fn () => request()->get('pembelian_id'))
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => static::fillFromPembelian($state, $set))
                            ->afterStateHydrated(fn ($state, callable $set) => static::fillFromPembelian($state, $set)),

                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'nama_vendor')
                            ->disabled()
                            ->dehydrated(true)
                            ->required(),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_bruto')->hidden()->dehydrated(true),
                                TextInput::make('diskon_persen')->hidden()->dehydrated(true),

                                Select::make('bank')
                                    ->label('Bank')
                                    ->options([
                                        'BCA' => 'BCA',
                                        'Mandiri' => 'Mandiri',
                                        'BNI' => 'BNI',
                                        'BRI' => 'BRI',
                                        'CASH' => 'Tunai / Cash',
                                    ])
                                    ->default('BCA')
                                    ->required(),

                                TextInput::make('no_rekening')
                                    ->label('No. Rekening')
                                    ->default('-'),

                                TextInput::make('total_netto')
                                    ->label('Total Tagihan')
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->dehydrated(true),
                            ])
                            ->columnSpanFull(),
                            
                        FileUpload::make('bukti_pembayaran')
                            ->label('Upload Bukti Pembayaran')
                            ->image()
                            ->directory('bukti-pembayaran') // will save to storage/app/public/bukti-pembayaran
                            ->maxSize(5120) // max 5MB
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /* =====================================================
     | TABLE
     ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_faktur')->label('Tanggal')->date()->sortable(),
                TextColumn::make('nomor_faktur_vendor')->label('Nomor Faktur')->searchable(),
                TextColumn::make('vendor.nama_vendor')->label('Vendor')->searchable(),
                TextColumn::make('total_netto')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.'))
                    ->placeholder('Rp 0'),

            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->action(fn (FakturPembelian $record) => redirect(static::getUrl('view', ['record' => $record]))),

                // (Opsional) kalau mau benar-benar “flow”, Anda bisa hide edit/delete:
                // EditAction::make(),
                // DeleteAction::make(),

                // EditAction::make(),
                // DeleteAction::make(),
            ]);
    }

    /* =====================================================
     | PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index'  => ListFakturPembelians::route('/'),
            'create' => CreateFakturPembelian::route('/create'),
            'view'   => Pages\ViewFakturPembelian::route('/{record}'),
        ];
    }
}