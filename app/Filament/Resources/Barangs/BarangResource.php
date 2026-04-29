<?php

namespace App\Filament\Resources\Barangs;

use App\Filament\Resources\Barangs\Pages\CreateBarang;
use App\Filament\Resources\Barangs\Pages\EditBarang;
use App\Filament\Resources\Barangs\Pages\ListBarangs;
use App\Filament\Resources\Barangs\Pages\ViewBarangs;
use App\Filament\Resources\Barangs\Schemas\BarangForm;
use App\Filament\Resources\Barangs\Tables\BarangsTable;
use App\Models\Barang;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Filament\Traits\HasRoleAccess;

class BarangResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['operasional'];
    protected static ?string $model = Barang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // ⬅️ baris INI yang penting: tipenya harus UnitEnum|string|null
    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Barang';

    protected static ?string $pluralModelLabel = 'Daftar Barang';

    protected static ?string $recordTitleAttribute = 'nama_barang';

    public static function form(Schema $schema): Schema
    {
        return BarangForm::configure($schema);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return BarangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
        'index'  => Pages\ListBarangs::route('/'),
        'create' => Pages\CreateBarang::route('/create'),
        'edit'   => Pages\EditBarang::route('/{record}/edit'),
        'view'   => Pages\ViewBarang::route('/{record}'),
        ];
    }
}