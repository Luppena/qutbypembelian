<?php

namespace App\Filament\Resources\Jurnals;

use App\Filament\Resources\Jurnals\Pages;
use App\Models\Jurnal;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Traits\HasRoleAccess;

class JurnalResource extends Resource
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static ?string $model = Jurnal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static ?string $pluralModelLabel = 'Jurnal Umum';
    protected static ?string $modelLabel = 'Jurnal';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Jurnal')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('tanggal')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('referensi')
                        ->label('Nomor Referensi (Opsional)'),
                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan / Uraian')
                        ->columnSpanFull(),
                ]),

            Section::make('Detail Jurnal')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship('details')
                        ->columns(4)
                        ->schema([
                            Forms\Components\Select::make('daftar_akun_id')
                                ->label('Akun')
                                ->relationship('akun', 'nama_akun')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\TextInput::make('keterangan')
                                ->label('Keterangan Baris')
                                ->nullable(),
                            Forms\Components\TextInput::make('debit')
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Forms\Components\TextInput::make('kredit')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ])
                        ->minItems(2)
                        ->addActionLabel('Tambah Baris Akun'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('referensi')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(50)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('details_count')
                    ->label('Baris Akun')
                    ->counts('details')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari_tanggal'], fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                            ->when($data['sampai_tanggal'], fn ($q, $v) => $q->whereDate('tanggal', '<=', $v));
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJurnals::route('/'),
            'create' => Pages\CreateJurnal::route('/create'),
            'edit'   => Pages\EditJurnal::route('/{record}/edit'),
        ];
    }
}
