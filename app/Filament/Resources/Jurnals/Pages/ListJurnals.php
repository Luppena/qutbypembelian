<?php

namespace App\Filament\Resources\Jurnals\Pages;

use App\Filament\Resources\Jurnals\JurnalResource;
use App\Models\Jurnal;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ListJurnals extends Page
{
    protected static string $resource = JurnalResource::class;

    protected string $view = 'filament.resources.jurnals.pages.list-jurnals';

    public function getTitle(): string
    {
        return 'Jurnal Umum';
    }

    public function getHeading(): string
    {
        return 'Jurnal Umum';
    }

    public function getBreadcrumb(): string
    {
        return 'Jurnal Umum';
    }

    public ?string $bulan = null;
    public ?string $tahun = null;

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getJurnals()
    {
        return Jurnal::with(['details.akun'])
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
    }

    public function getPeriodeLabel(): string
    {
        $bulanNama = [
            '01' => 'Januari',  '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober',  '11' => 'November',  '12' => 'Desember',
        ];
        return 'Periode ' . ($bulanNama[$this->bulan] ?? '-') . ' ' . $this->tahun;
    }

    public function getCreateUrl(): string
    {
        return route('filament.admin.resources.jurnals.create');
    }

    public function getEditUrl(Jurnal $jurnal): string
    {
        return route('filament.admin.resources.jurnals.edit', ['record' => $jurnal->id]);
    }
}
