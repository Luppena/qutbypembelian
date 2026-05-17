<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Filament\Traits\HasRoleAccess;
use App\Models\GrnDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BarangMasukResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['admin', 'operasional', 'gudang'];

    protected static ?string $model = GrnDetail::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static UnitEnum|string|null $navigationGroup = 'Transaksi Pembelian';
    protected static ?string $navigationLabel = 'Barang Masuk';
    protected static ?string $pluralModelLabel = 'Daftar Barang Masuk';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn.tanggal_terima')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('grn.nomor_grn')
                    ->label('No. GRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grn.pembelian.nomor')
                    ->label('No. PO')
                    ->searchable(),

                TextColumn::make('grn.vendor.nama_vendor')
                    ->label('Vendor')
                    ->searchable(),

                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('qty_masuk_baik')
                    ->label('Qty Masuk')
                    ->getStateUsing(fn (GrnDetail $record): int => max(0, (int) $record->qty_diterima - (int) ($record->qty_rusak ?? 0)))
                    ->alignCenter()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('qty_diterima', $direction)),

                TextColumn::make('pembelianDetail.harga')
                    ->label('Harga/Unit')
                    ->money('IDR', locale: 'id'),
            ])
            ->filters([])
            ->recordActions([])
            ->headerActions([])
            ->emptyStateHeading('Belum ada barang yang diterima');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangMasuk::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('grn', fn (Builder $query) => $query->where('status', 'dikonfirmasi'))
            ->where('qty_diterima', '>', 0)
            ->where('kondisi', 'baik')
            ->whereRaw('(qty_diterima - COALESCE(qty_rusak, 0)) > 0')
            ->with(['barang', 'pembelianDetail', 'grn.vendor', 'grn.pembelian'])
            ->latest('id');
    }
}
