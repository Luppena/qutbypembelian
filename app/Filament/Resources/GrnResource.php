<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GrnResource\Pages;
use App\Models\Grn;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRoleAccess;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use BackedEnum;
use UnitEnum;

class GrnResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['admin', 'operasional', 'gudang'];
    protected static ?string $model = Grn::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static UnitEnum|string|null   $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Penerimaan Barang';
    protected static ?string $pluralModelLabel = 'Daftar Penerimaan Barang';
    protected static ?int    $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Grn::where('status', 'draft')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /* ===========================
     | FORM
     =========================== */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([

            Section::make('Informasi GRN')
                ->columns(3)
                ->schema([
                    TextInput::make('nomor_grn')
                        ->label('Nomor GRN')
                        ->default(fn () => Grn::generateNomor())
                        ->disabled()
                        ->dehydrated(),

                    Select::make('pembelian_id')
                        ->label('Pilih Purchase Order')
                        ->options(
                            Pembelian::whereIn('status', ['menunggu', 'partial', 'dikirim', 'sebagian'])
                                ->get()
                                ->pluck('nomor', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if (! $state) return;
                            $po = Pembelian::with(['vendor', 'details.barang'])->find($state);
                            if (! $po) return;
                            $set('vendor_id', $po->vendor_id);

                            // Auto-populate detail items dari PO
                            $items = self::getOpenGrnItems($po);

                            $set('details', $items);
                        }),

                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->relationship('vendor', 'nama_vendor')
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('tanggal_terima')
                        ->label('Tanggal Terima')
                        ->default(now())
                        ->required(),

                    Textarea::make('catatan')
                        ->label('Catatan Penerimaan')
                        ->columnSpanFull()
                        ->rows(2)
                        ->placeholder('Keterangan selisih, kondisi umum, dll.'),
                ]),

            Section::make('Detail Penerimaan Per Item')
                ->description('Isi qty aktual dan kondisi tiap barang yang diterima.')
                ->schema([
                    Repeater::make('details')
                        ->relationship('details')
                        ->label('')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): array => self::ensurePembelianDetailId($data, $get))
                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, Get $get): array => self::ensurePembelianDetailId($data, $get))
                        ->columns(6)
                        ->schema([
                            Select::make('barang_id')
                                ->label('Barang')
                                ->relationship('barang', 'nama_barang')
                                ->searchable()
                                ->preload()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),

                            Hidden::make('pembelian_detail_id')
                                ->dehydrated(),


                            TextInput::make('qty_po')
                                ->label('Qty PO')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('satuan')
                                ->hidden()
                                ->dehydrated(false),

                            TextInput::make('qty_sudah_diterima')
                                ->hidden()
                                ->dehydrated(false),

                            TextInput::make('qty_diterima')
                                ->label('Qty Aktual')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->live()
                                ->extraInputAttributes(fn (Get $get) => [
                                    'class' => self::getQtyIndicator($get)['inputClass'],
                                ]),

                            Select::make('kondisi')
                                ->label('Kondisi')
                                ->options([
                                    'baik'           => '✅ Baik',
                                    'rusak_sebagian' => '⚠️ Rusak Sebagian',
                                    'rusak_semua'    => '❌ Rusak Semua',
                                ])
                                ->default('baik')
                                ->disabled(fn (Get $get) => (int) ($get('qty_diterima') ?? 0) === 0)
                                ->required(fn (Get $get) => (int) ($get('qty_diterima') ?? 0) > 0)
                                ->live(),

                            FileUpload::make('foto')
                                ->label('Foto Kondisi')
                                ->disk('public')
                                ->directory('grn-photos')
                                ->disabled(fn (Get $get) => (int) ($get('qty_diterima') ?? 0) === 0)
                                ->required(fn (Get $get) => (int) ($get('qty_diterima') ?? 0) > 0)
                                ->columnSpan(2),

                            Placeholder::make('indikator_qty')
                                ->label('')
                                ->content(fn (Get $get) => self::renderQtyIndicator($get))
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function getOpenGrnItems(Pembelian $po): array
    {
        $po->loadMissing(['details.barang', 'details.grnDetails.grn']);

        return $po->details
            ->filter(fn (PembelianDetail $detail) => $detail->qty_outstanding > 0)
            ->map(fn (PembelianDetail $detail) => [
                'pembelian_detail_id' => $detail->id,
                'barang_id'           => $detail->barang_id,
                'qty_po'              => $detail->qty,
                'qty_diterima'        => $detail->qty_outstanding,
                'satuan'              => $detail->satuan,
                'qty_sudah_diterima'  => $detail->qty_diterima,
                'kondisi'             => 'baik',
                'foto'                => null,
                'catatan_item'        => null,
            ])
            ->values()
            ->toArray();
    }

    protected static function getQtyIndicator(Get $get): array
    {
        $qtyPo = (int) ($get('qty_po') ?? 0);
        $qtyAktual = (int) ($get('qty_diterima') ?? 0);
        $qtySudahDiterima = (int) ($get('qty_sudah_diterima') ?? 0);
        $qtyTarget = max(0, $qtyPo - $qtySudahDiterima);
        $satuan = trim((string) ($get('satuan') ?? ''));
        $unit = $satuan !== '' ? ' ' . $satuan : '';
        $targetLabel = $qtySudahDiterima > 0 ? 'outstanding PO' : 'PO';

        if ($qtyAktual === 0) {
            return [
                'badge' => 'Belum Diterima',
                'message' => 'Barang belum diterima',
                'note' => 'Field Kondisi & Foto dikunci / tidak wajib diisi',
                'panelClass' => 'border-red-300 bg-red-50 text-red-800',
                'badgeClass' => 'bg-red-100 text-red-700 ring-red-200',
                'inputClass' => '!border-red-400 focus:!border-red-500 focus:!ring-red-500',
            ];
        }

        if ($qtyAktual < $qtyTarget) {
            $kurang = $qtyTarget - $qtyAktual;

            return [
                'badge' => "Kurang {$kurang}{$unit}",
                'message' => "Kurang {$kurang}{$unit} dari {$targetLabel}",
                'note' => 'Sisa akan dicatat sebagai Outstanding',
                'panelClass' => 'border-yellow-300 bg-yellow-50 text-yellow-900',
                'badgeClass' => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
                'inputClass' => '!border-yellow-400 focus:!border-yellow-500 focus:!ring-yellow-500',
            ];
        }

        if ($qtyAktual === $qtyTarget) {
            return [
                'badge' => 'Lengkap',
                'message' => 'Qty diterima sudah lengkap sesuai PO',
                'note' => '',
                'panelClass' => 'border-green-300 bg-green-50 text-green-800',
                'badgeClass' => 'bg-green-100 text-green-700 ring-green-200',
                'inputClass' => '!border-green-400 focus:!border-green-500 focus:!ring-green-500',
            ];
        }

        $lebih = $qtyAktual - $qtyTarget;

        return [
            'badge' => "Lebih {$lebih}{$unit}",
            'message' => "Lebih {$lebih}{$unit} dari {$targetLabel}",
            'note' => 'Over quantity perlu dikonfirmasi sebelum GRN diproses',
            'panelClass' => 'border-blue-300 bg-blue-50 text-blue-800',
            'badgeClass' => 'bg-blue-100 text-blue-700 ring-blue-200',
            'inputClass' => '!border-blue-400 focus:!border-blue-500 focus:!ring-blue-500',
        ];
    }

    protected static function renderQtyIndicator(Get $get): HtmlString
    {
        $indicator = self::getQtyIndicator($get);
        $note = $indicator['note'] !== ''
            ? '<div class="mt-1 text-xs opacity-80">' . e($indicator['note']) . '</div>'
            : '';

        return new HtmlString(
            '<div class="rounded-lg border px-3 py-2 ' . e($indicator['panelClass']) . '">' .
                '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . e($indicator['badgeClass']) . '">' .
                    e($indicator['badge']) .
                '</span>' .
                '<div class="mt-2 text-sm font-medium">' . e($indicator['message']) . '</div>' .
                $note .
            '</div>'
        );
    }

    protected static function ensurePembelianDetailId(array $data, Get $get): array
    {
        if (! empty($data['pembelian_detail_id'])) {
            return $data;
        }

        $pembelianId = $get('../../pembelian_id') ?? $get('pembelian_id');
        $barangId = $data['barang_id'] ?? null;

        if (! $pembelianId || ! $barangId) {
            return $data;
        }

        $pembelianDetailId = PembelianDetail::query()
            ->where('pembelian_id', $pembelianId)
            ->where('barang_id', $barangId)
            ->value('id');

        if ($pembelianDetailId) {
            $data['pembelian_detail_id'] = $pembelianDetailId;
        }

        return $data;
    }

    /* ===========================
     | TABLE
     =========================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_grn')
                    ->label('Nomor GRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pembelian.nomor')
                    ->label('No. PO')
                    ->searchable(),

                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor'),

                TextColumn::make('tanggal_terima')
                    ->label('Tgl. Terima')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft'        => 'Draft',
                        'dikonfirmasi' => 'Diterima',
                        default        => ucfirst((string) $state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'draft'        => 'warning',
                        'dikonfirmasi' => 'success',
                        default        => 'gray',
                    }),

                TextColumn::make('dikonfirmasi_at')
                    ->label('Dikonfirmasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum dikonfirmasi'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'        => 'Draft',
                        'dikonfirmasi' => 'Diterima',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                EditAction::make()
                    ->label('Edit')
                    ->visible(fn ($record) => $record->status === 'draft'),
            ])
            ->emptyStateHeading('Belum ada GRN yang dibuat');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGrns::route('/'),
            'create' => Pages\CreateGrn::route('/create'),
            'edit'   => Pages\EditGrn::route('/{record}/edit'),
            'view'   => Pages\ViewGrn::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['pembelian', 'vendor'])
            ->latest();
    }
}
