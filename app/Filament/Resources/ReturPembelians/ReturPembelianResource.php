<?php

namespace App\Filament\Resources\ReturPembelians;

use App\Filament\Resources\ReturPembelians\Pages;
use App\Filament\Traits\HasRoleAccess;
use App\Models\Grn;
use App\Models\GrnDetail;
use App\Models\KartuStok;
use App\Models\Pembelian;
use App\Models\ReturPembelian;
use App\Models\StokFifoLayer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ReturPembelianResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['admin', 'operasional'];
    protected static ?string $model = ReturPembelian::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Retur Pembelian';
    protected static ?string $pluralModelLabel = 'Daftar Retur Pembelian';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Informasi Retur')
                ->columns(3)
                ->schema([
                    TextInput::make('nomor_retur')
                        ->label('No. Retur')
                        ->default(fn () => ReturPembelian::generateNomor())
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    DatePicker::make('tanggal_retur')
                        ->label('Tanggal Retur')
                        ->default(now())
                        ->required(),

                    Select::make('pembelian_id')
                        ->label('No. PO')
                        ->options(fn () => Pembelian::query()
                            ->whereHas('grns', fn (Builder $query) => $query->where('status', 'dikonfirmasi'))
                            ->orderByDesc('tanggal')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Pembelian $po) => [$po->id => $po->nomor]))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateHydrated(fn (Set $set, $state) => static::hydratePoFields($set, $state, false))
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('details', []);
                            static::hydratePoFields($set, $state);
                        }),

                    TextInput::make('vendor_label')
                        ->label('Vendor')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('status_po')
                        ->label('Status PO')
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('po_warning')
                        ->label('')
                        ->content(new HtmlString('<div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">PO ini belum memiliki GRN yang dikonfirmasi.<br>Retur hanya bisa dibuat dari barang yang sudah diterima.</div>'))
                        ->visible(fn (Get $get) => filled($get('pembelian_id')) && ! static::poHasConfirmedGrn($get('pembelian_id')))
                        ->columnSpanFull(),

                    Hidden::make('grn_id')->dehydrated(),
                    Hidden::make('vendor_id')->dehydrated(),
                    Hidden::make('status')->default('menunggu')->dehydrated(),
                ]),

            Section::make('Detail Barang yang Diretur')
                ->visible(fn (Get $get) => static::poHasConfirmedGrn($get('pembelian_id')))
                ->schema([
                    Repeater::make('details')
                        ->label('Barang Retur')
                        ->relationship('details')
                        ->addActionLabel('Tambah Barang Retur')
                        ->columns(4)
                        ->minItems(1)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::prepareDetailData($data))
                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::prepareDetailData($data))
                        ->schema([
                            Select::make('grn_detail_id')
                                ->label('Barang')
                                ->options(fn (Get $get) => static::getReturBarangOptions($get('../../pembelian_id')))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateHydrated(fn (Set $set, $state) => static::hydrateDetailFields($set, $state, false))
                                ->afterStateUpdated(fn (Set $set, $state) => static::hydrateDetailFields($set, $state)),

                            TextInput::make('qty_diterima_display')
                                ->label('Qty Diterima')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('qty_retur')
                                ->label('Qty Diretur')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn (Get $get) => (int) ($get('qty_diterima_display') ?? 0))
                                ->live()
                                ->extraInputAttributes(fn (Get $get) => [
                                    'class' => static::getQtyReturIndicator($get)['inputClass'],
                                ])
                                ->helperText(fn (Get $get) => static::getQtyReturIndicator($get)['message'])
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $set('subtotal', (int) ($state ?? 0) * (float) ($get('harga_satuan') ?? 0));
                                }),

                            TextInput::make('satuan_display')
                                ->label('Satuan')
                                ->disabled()
                                ->dehydrated(false),

                            Hidden::make('barang_id')->dehydrated(),
                            Hidden::make('harga_satuan')->default(0)->dehydrated(),
                            Hidden::make('subtotal')->default(0)->dehydrated(),
                        ]),
                ]),

            Section::make('Alasan & Penyelesaian')
                ->visible(fn (Get $get) => static::poHasConfirmedGrn($get('pembelian_id')))
                ->columns(2)
                ->schema([
                    Select::make('alasan_utama')
                        ->label('Alasan retur')
                        ->options([
                            'rusak' => 'Barang rusak/cacat',
                            'tidak_sesuai' => 'Tidak sesuai pesanan',
                            'kelebihan_qty' => 'Kelebihan qty',
                            'kualitas_tidak_sesuai' => 'Kualitas tidak sesuai',
                            'salah_kirim' => 'Barang salah kirim',
                        ])
                        ->required(),

                    Select::make('penyelesaian')
                        ->label('Penyelesaian')
                        ->options([
                            'barang_pengganti' => 'Barang pengganti',
                            'uang_potong_tagihan' => 'Kembalikan uang / potong tagihan',
                        ])
                        ->helperText('Barang pengganti: stok berkurang saat retur, bertambah lagi saat pengganti diterima. Uang/potong tagihan: stok berkurang permanen.')
                        ->required(),

                    FileUpload::make('foto_bukti')
                        ->label('Foto bukti')
                        ->disk('public')
                        ->directory('retur-pembelian')
                        ->image()
                        ->columnSpanFull(),

                    Textarea::make('keterangan')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nomor_retur')->label('No. Retur')->searchable()->sortable(),
                TextColumn::make('tanggal_retur')->label('Tanggal')->date()->sortable(),
                TextColumn::make('pembelian.nomor')->label('No. PO')->searchable(),
                TextColumn::make('vendor.nama_vendor')->label('Vendor')->searchable(),
                TextColumn::make('alasan_utama')->label('Alasan')->formatStateUsing(fn ($state) => match ($state) {
                    'rusak' => 'Barang rusak/cacat',
                    'tidak_sesuai' => 'Tidak sesuai pesanan',
                    'kelebihan_qty' => 'Kelebihan qty',
                    'kualitas_tidak_sesuai' => 'Kualitas tidak sesuai',
                    'salah_kirim' => 'Barang salah kirim',
                    default => ucfirst((string) $state),
                }),
                TextColumn::make('penyelesaian')->label('Penyelesaian')->formatStateUsing(fn ($state) => match ($state) {
                    'barang_pengganti' => 'Barang pengganti',
                    'uang_potong_tagihan' => 'Kembalikan uang / potong tagihan',
                    default => '-',
                }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'disetujui',
                        'success' => 'selesai',
                        'danger' => 'dibatalkan',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'menunggu')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Retur Pembelian')
                    ->modalDescription('Retur akan disetujui dan stok otomatis dikurangi sesuai qty retur.')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function (ReturPembelian $record) {
                        DB::transaction(function () use ($record) {
                            $record->load('details.barang');

                            foreach ($record->details as $detail) {
                                if ($detail->barang && $detail->qty_retur > 0) {
                                    $detail->barang->decrement('stok', $detail->qty_retur);
                                    static::consumeReturFifo($detail);

                                    KartuStok::create([
                                        'barang_id' => $detail->barang_id,
                                        'tanggal' => $record->tanggal_retur ?? now(),
                                        'keterangan' => 'Retur Pembelian ' . $record->nomor_retur,
                                        'masuk' => 0,
                                        'harga_masuk' => 0,
                                        'keluar' => (int) $detail->qty_retur,
                                        'harga_keluar' => (int) ($detail->harga_satuan ?? 0),
                                        'source_type' => 'retur_pembelian',
                                        'source_id' => $record->id,
                                        'source_line_id' => $detail->id,
                                    ]);
                                }
                            }

                            $record->update([
                                'status' => 'disetujui',
                                'disetujui_oleh' => auth()->id(),
                                'disetujui_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Retur disetujui')
                            ->body('Stok barang telah dikurangi sesuai qty retur.')
                            ->success()
                            ->send();
                    }),

                ViewAction::make()->label('Lihat'),
                EditAction::make()->label('Edit')->visible(fn ($record) => $record->status === 'menunggu'),
                DeleteAction::make()->label('Hapus')->visible(fn ($record) => $record->status === 'menunggu'),
            ]);
    }

    public static function poHasConfirmedGrn(mixed $poId): bool
    {
        return filled($poId) && Grn::query()
            ->where('pembelian_id', $poId)
            ->where('status', 'dikonfirmasi')
            ->exists();
    }

    public static function getReturBarangOptions(mixed $poId): array
    {
        if (! $poId) {
            return [];
        }

        return GrnDetail::query()
            ->with(['barang', 'grn'])
            ->whereHas('grn', fn (Builder $query) => $query
                ->where('pembelian_id', $poId)
                ->where('status', 'dikonfirmasi'))
            ->where('qty_diterima', '>', 0)
            ->get()
            ->mapWithKeys(fn (GrnDetail $detail) => [
                $detail->id => ($detail->barang->nama_barang ?? '-') . ' - diterima ' . (int) $detail->qty_diterima,
            ])
            ->toArray();
    }

    public static function hydratePoFields(Set $set, mixed $poId, bool $resetDetails = true): void
    {
        $set('grn_id', null);
        $set('vendor_id', null);
        $set('vendor_label', null);
        $set('status_po', null);

        if (! $poId) {
            return;
        }

        $po = Pembelian::with(['vendor', 'grns' => fn ($query) => $query->where('status', 'dikonfirmasi')])->find($poId);

        $set('vendor_id', $po?->vendor_id);
        $set('vendor_label', $po?->vendor?->nama_vendor ?? '-');
        $set('status_po', ucfirst((string) ($po?->status ?? '-')));
        $set('grn_id', $po?->grns?->first()?->id);
    }

    public static function hydrateDetailFields(Set $set, mixed $grnDetailId, bool $resetSubtotal = true): void
    {
        $detail = GrnDetail::with(['barang', 'pembelianDetail'])->find($grnDetailId);

        if (! $detail) {
            $set('barang_id', null);
            $set('qty_diterima_display', null);
            $set('satuan_display', null);
            $set('harga_satuan', 0);
            if ($resetSubtotal) {
                $set('subtotal', 0);
            }
            return;
        }

        $harga = (float) ($detail->pembelianDetail?->harga_satuan ?? $detail->pembelianDetail?->harga ?? 0);

        $set('barang_id', $detail->barang_id);
        $set('qty_diterima_display', (int) $detail->qty_diterima);
        $set('satuan_display', $detail->barang?->satuan ?? $detail->pembelianDetail?->satuan ?? '-');
        $set('harga_satuan', $harga);

        if ($resetSubtotal) {
            $set('subtotal', 0);
        }
    }

    public static function getQtyReturIndicator(Get $get): array
    {
        $qtyDiterima = (int) ($get('qty_diterima_display') ?? 0);
        $qtyRetur = (int) ($get('qty_retur') ?? 0);

        if ($qtyRetur <= 0) {
            return [
                'message' => 'Qty diretur wajib diisi',
                'inputClass' => '!border-red-500 focus:!border-red-500 focus:!ring-red-500',
            ];
        }

        if ($qtyDiterima > 0 && $qtyRetur > $qtyDiterima) {
            return [
                'message' => "⚠ Melebihi qty diterima (maks. {$qtyDiterima})",
                'inputClass' => '!border-red-500 focus:!border-red-500 focus:!ring-red-500',
            ];
        }

        return [
            'message' => null,
            'inputClass' => '',
        ];
    }

    public static function prepareDetailData(array $data): array
    {
        $detail = GrnDetail::with('pembelianDetail')->find($data['grn_detail_id'] ?? null);

        if ($detail) {
            $data['barang_id'] = $detail->barang_id;
            $data['harga_satuan'] = (float) ($detail->pembelianDetail?->harga_satuan ?? $detail->pembelianDetail?->harga ?? $data['harga_satuan'] ?? 0);
            $data['subtotal'] = (int) ($data['qty_retur'] ?? 0) * (float) $data['harga_satuan'];
        }

        return $data;
    }

    public static function consumeReturFifo($detail): void
    {
        $remaining = (int) $detail->qty_retur;

        StokFifoLayer::query()
            ->where('barang_id', $detail->barang_id)
            ->where('qty_sisa', '>', 0)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->each(function (StokFifoLayer $layer) use (&$remaining) {
                if ($remaining <= 0) {
                    return false;
                }

                $taken = min($remaining, (int) $layer->qty_sisa);
                $layer->decrement('qty_sisa', $taken);
                $remaining -= $taken;

                return null;
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['grn', 'vendor', 'pembelian', 'details'])
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturPembelian::route('/'),
            'create' => Pages\CreateReturPembelian::route('/create'),
            'edit' => Pages\EditReturPembelian::route('/{record}/edit'),
            'view' => Pages\ViewReturPembelian::route('/{record}'),
        ];
    }
}
