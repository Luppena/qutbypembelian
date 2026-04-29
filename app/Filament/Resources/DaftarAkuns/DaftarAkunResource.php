<?php

namespace App\Filament\Resources\DaftarAkuns;

use App\Filament\Resources\DaftarAkuns\Pages\CreateDaftarAkun;
use App\Filament\Resources\DaftarAkuns\Pages\EditDaftarAkun;
use App\Filament\Resources\DaftarAkuns\Pages\ListDaftarAkuns;
use App\Filament\Resources\DaftarAkuns\Schemas\DaftarAkunForm;
use App\Filament\Resources\DaftarAkuns\Tables\DaftarAkunsTable;
use App\Filament\Traits\HasRoleAccess;
use App\Models\DaftarAkun;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DaftarAkunResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];

    protected static ?string $model = DaftarAkun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $pluralModelLabel = 'Daftar Akun';
    protected static ?string $navigationLabel = 'Daftar Akun';
    protected static ?string $recordTitleAttribute = 'nama_akun';

    public static function form(Schema $schema): Schema
    {
        return DaftarAkunForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DaftarAkunsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDaftarAkuns::route('/'),
            'create' => CreateDaftarAkun::route('/create'),
            'edit'   => EditDaftarAkun::route('/{record}/edit'),
        ];
    }
}
