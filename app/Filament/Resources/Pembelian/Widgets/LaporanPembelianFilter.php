<?php

namespace App\Filament\Resources\Pembelian\Widgets;

use App\Models\Vendor;
use Filament\Widgets\Widget;

class LaporanPembelianFilter extends Widget
{
    protected string $view = 'filament.widgets.laporan-pembelian-filter';

    protected int | string | array $columnSpan = 'full';

    public string $bulan = '';
    public string $tahun = '';
    public string $vendor_id = '';
    public string $status = '';

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = (string) now()->year;
    }

    public function getVendorOptions(): array
    {
        return Vendor::orderBy('nama_vendor')
            ->pluck('nama_vendor', 'id')
            ->toArray();
    }

    public function cetakPdf(): void
    {
        $params = http_build_query(array_filter([
            'bulan'     => $this->bulan,
            'tahun'     => $this->tahun,
            'vendor_id' => $this->vendor_id,
            'status'    => $this->status,
        ]));

        $url = route('laporan-pembelian.pdf') . '?' . $params;

        $this->js("window.open('{$url}', '_blank')");
    }

    public function unduhPdf(): void
    {
        $params = http_build_query(array_filter([
            'bulan'     => $this->bulan,
            'tahun'     => $this->tahun,
            'vendor_id' => $this->vendor_id,
            'status'    => $this->status,
            'download'  => '1',
        ]));

        $url = route('laporan-pembelian.pdf') . '?' . $params;

        $this->js("window.open('{$url}', '_blank')");
    }
}
