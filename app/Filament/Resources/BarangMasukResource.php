<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Models\Pembelian;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRoleAccess;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class BarangMasukResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['admin', 'operasional', 'gudang'];
    protected static ?string $model = Pembelian::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static UnitEnum|string|null   $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Barang Masuk';
    protected static ?string $pluralModelLabel = 'Daftar Barang Masuk (PO)';
    protected static ?int    $navigationSort = 1;

    /* ── badge notif di sidebar ── */
    public static function getNavigationBadge(): ?string
    {
        try {
            $count = Pembelian::whereIn('status', ['menunggu', 'partial'])
                ->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /* ── FORM — read only ── */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(1)->schema([
                Section::make('Informasi Purchase Order')
                    ->schema([
                        Html::make(fn (?Pembelian $record) => self::renderPoInfo($record)),
                    ]),

                Section::make('Detail Item PO')
                    ->schema([
                        Html::make(fn (?Pembelian $record) => self::renderPoItems($record)),
                    ]),
            ]),
        ]);
    }

    protected static function renderPoInfo(?Pembelian $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('');
        }

        $record->loadMissing(['vendor', 'grns']);

        $estimasiDatang = self::getEstimasiDatang($record);

        return new HtmlString('
            <div class="grid grid-cols-2 gap-5 text-sm">
                ' . self::renderInfoItem('Nomor PO', e($record->nomor ?? '-')) . '
                ' . self::renderInfoItem('Vendor', e($record->vendor?->nama_vendor ?? '-')) . '
                ' . self::renderInfoItem('Tanggal PO', e($record->tanggal?->format('d/m/Y') ?? '-')) . '
                ' . self::renderInfoItem('Estimasi Datang', e($estimasiDatang?->format('d/m/Y') ?? '-')) . '
                ' . self::renderInfoItem('Status Pengiriman', e(self::formatStatusPengiriman($record))) . '
                ' . self::renderInfoItem('Status PO', e(self::formatStatusPo($record->status))) . '
            </div>
        ');
    }

    protected static function getEstimasiDatang(Pembelian $record): mixed
    {
        $record->loadMissing('grns');

        return $record->grns
            ->filter(fn ($grn) => filled($grn->tanggal_terima))
            ->sortByDesc('tanggal_terima')
            ->first()?->tanggal_terima
            ?? $record->estimasi_datang;
    }

    protected static function renderInfoItem(string $label, string $value): string
    {
        return '
            <div>
                <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">' . e($label) . '</div>
                <div class="min-h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-medium text-gray-900">' . $value . '</div>
            </div>
        ';
    }

    protected static function renderPoItems(?Pembelian $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('');
        }

        $record->loadMissing(['details.barang', 'details.grnDetails.grn']);

        $rows = $record->details->map(function ($detail) {
            $qtyPo = (int) $detail->qty;
            $qtyDiterima = (int) $detail->qty_diterima;
            $outstanding = max(0, $qtyPo - $qtyDiterima);
            $satuan = (string) ($detail->satuan ?? '');

            return '
                <tr class="border-b border-gray-200 last:border-b-0">
                    <td class="px-3 py-3 font-medium text-gray-900">' . e($detail->barang?->nama_barang ?? '-') . '</td>
                    <td class="px-3 py-3 text-center">' . number_format($qtyPo, 0, ',', '.') . '</td>
                    <td class="px-3 py-3 text-center">' . number_format($qtyDiterima, 0, ',', '.') . '</td>
                    <td class="px-3 py-3 text-center">' . number_format($outstanding, 0, ',', '.') . '</td>
                    <td class="px-3 py-3">' . self::renderStatusItemBadge($qtyDiterima, $outstanding, $satuan) . '</td>
                    <td class="px-3 py-3 text-center">' . e($satuan ?: '-') . '</td>
                    <td class="px-3 py-3 text-right">Rp ' . number_format((float) $detail->harga, 0, ',', '.') . '</td>
                </tr>
            ';
        })->implode('');

        $totalItem = $record->details->count();
        $sudahLengkap = $record->details->filter(fn ($detail) => (int) $detail->qty_outstanding === 0)->count();
        $masihKurang = $totalItem - $sudahLengkap;

        return new HtmlString('
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-gray-100">
                            <tr class="border-b border-gray-200">
                                <th class="px-3 py-3 text-left font-bold text-gray-700">Barang</th>
                                <th class="px-3 py-3 text-center font-bold text-gray-700">Qty PO</th>
                                <th class="px-3 py-3 text-center font-bold text-gray-700">Qty Diterima</th>
                                <th class="px-3 py-3 text-center font-bold text-gray-700">Outstanding</th>
                                <th class="px-3 py-3 text-left font-bold text-gray-700">Status Item</th>
                                <th class="px-3 py-3 text-center font-bold text-gray-700">Satuan</th>
                                <th class="px-3 py-3 text-right font-bold text-gray-700">Harga/Unit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">' . ($rows ?: '<tr><td colspan="7" class="px-3 py-4 text-center text-gray-500">Tidak ada item PO.</td></tr>') . '</tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm md:grid-cols-3">
                <div><span class="text-gray-500">Total Item:</span> <span class="font-semibold text-gray-900">' . $totalItem . '</span></div>
                <div><span class="text-gray-500">Sudah Lengkap:</span> <span class="font-semibold text-green-700">' . $sudahLengkap . ' item</span></div>
                <div><span class="text-gray-500">Masih Kurang:</span> <span class="font-semibold text-amber-700">' . $masihKurang . ' item</span></div>
            </div>
        ');
    }

    protected static function formatStatusPengiriman(Pembelian $record): string
    {
        $state = match ($record->status) {
            'partial' => 'sebagian',
            'selesai' => 'selesai',
            default => $record->status_pengiriman,
        };

        return match ($state) {
            'dalam_kirim' => 'Dalam Pengiriman',
            'sebagian' => 'Sebagian Diterima',
            'selesai' => 'Selesai',
            default => ucfirst(str_replace('_', ' ', (string) ($state ?: 'menunggu'))),
        };
    }

    protected static function formatStatusPo(?string $state): string
    {
        return match ($state) {
            'partial' => 'Partial',
            'selesai' => 'Selesai',
            'menunggu' => 'Menunggu',
            default => ucfirst((string) ($state ?: '-')),
        };
    }

    protected static function renderStatusItemBadge(int $qtyDiterima, int $outstanding, string $satuan): string
    {
        $unit = $satuan !== '' ? ' ' . $satuan : '';

        if ($qtyDiterima === 0) {
            return self::badge('Belum Diterima', 'bg-red-100 text-red-700 ring-red-200');
        }

        if ($outstanding > 0) {
            return self::badge('Kurang ' . number_format($outstanding, 0, ',', '.') . $unit, 'bg-yellow-100 text-yellow-800 ring-yellow-200');
        }

        return self::badge('Lengkap', 'bg-green-100 text-green-700 ring-green-200');
    }

    protected static function badge(string $label, string $class): string
    {
        return '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset ' . e($class) . '">' . e($label) . '</span>';
    }

    /* ── TABLE ── */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor')
                    ->searchable(),

                TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->suffix(' item'),

                TextColumn::make('estimasi_datang')
                    ->label('Est. Datang')
                    ->getStateUsing(fn (Pembelian $record) => self::getEstimasiDatang($record))
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status PO')
                    ->formatStateUsing(fn ($state) => self::formatStatusPo($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status PO')
                    ->options([
                        'menunggu'    => 'Menunggu',
                        'partial'     => 'Partial',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Detail'),
            ])
            ->headerActions([])
            ->emptyStateHeading('Tidak ada PO yang menunggu penerimaan');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangMasuk::route('/'),
            'view'  => Pages\ViewBarangMasuk::route('/{record}'),
        ];
    }

    /* ── disable create/edit/delete ── */
    public static function canCreate(): bool    { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', ['menunggu', 'partial'])
            ->with(['vendor', 'details.barang', 'grns'])
            ->latest();
    }
}
