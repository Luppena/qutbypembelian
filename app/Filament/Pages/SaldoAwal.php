<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasRoleAccess;
use App\Models\DaftarAkun;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class SaldoAwal extends Page implements HasForms
{
    use HasRoleAccess, InteractsWithForms;

    protected static array $allowedRoles = ['finance'];

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-currency-dollar';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan Keuangan';
    protected static ?string $navigationLabel = 'Saldo Awal';
    protected static ?string $title           = 'Saldo Awal';
    protected string $view = 'filament.pages.saldo-awal';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Input Saldo Awal')
                    ->description('Saldo awal digunakan sebagai posisi keuangan untuk memulai periode akuntansi berikutnya.')
                    ->schema([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options([
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ])
                            ->required(),
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;
                                for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++) {
                                    $years[$y] = $y;
                                }
                                return $years;
                            })
                            ->required(),
                        Select::make('daftar_akun_id')
                            ->label('Pilih Akun')
                            ->options(DaftarAkun::pluck('nama_akun', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('nominal')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        // Hanya tampilan sesuai permintaan user
    }
}
