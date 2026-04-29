<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Traits\HasRoleAccess;
use App\Services\KartuStokService;

class KartuStokPage extends Page
{
    use HasRoleAccess;

    // Hanya bisa diakses oleh Admin & Operasional
    protected static array $allowedRoles = ['operasional'];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Kartu Stok (FIFO)';
    protected static ?string $title           = 'Kartu Stok (Metode FIFO)';
    protected string $view = 'filament.pages.kartu-stok';

    public string $bulan = '';
    public string $tahun = '';

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    public function getTitle(): string     { return 'Kartu Stok'; }
    public function getHeading(): string   { return 'Laporan Kartu Stok (FIFO)'; }
    public function getBreadcrumb(): string { return 'Kartu Stok'; }

    public function getPeriodeLabel(): string
    {
        $nama = [
            '01' => 'Januari',  '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober',  '11' => 'November',  '12' => 'Desember',
        ];
        return ($nama[$this->bulan] ?? '-') . ' ' . $this->tahun;
    }

    public function getLaporanData(): array
    {
        return app(KartuStokService::class)
            ->getLaporanData($this->bulan, $this->tahun);
    }
}
