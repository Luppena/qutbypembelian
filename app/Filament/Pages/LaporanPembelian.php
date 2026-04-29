<?php

namespace App\Filament\Pages;

use App\Models\Pembelian;
use App\Models\Vendor;
use Carbon\Carbon;
use Filament\Pages\Page;
use App\Filament\Traits\HasRoleAccess;

class LaporanPembelian extends Page
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Pembelian';
    protected static ?string $title           = 'Laporan Pembelian';
    protected static ?int    $navigationSort  = 1;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.laporan-pembelian';

    public string $bulan     = '';
    public string $tahun     = '';
    public string $vendor_id = '';   // '' = semua supplier

    public function mount(): void
    {
        $earliest = Pembelian::orderBy('tanggal')->value('tanggal');
        if ($earliest) {
            $dt          = Carbon::parse($earliest);
            $this->bulan = $dt->format('m');
            $this->tahun = $dt->format('Y');
        } else {
            $this->bulan = now()->format('m');
            $this->tahun = now()->format('Y');
        }
    }

    public function getTitle(): string     { return 'Laporan Pembelian Barang'; }
    public function getHeading(): string   { return 'Laporan Pembelian Barang'; }
    public function getBreadcrumb(): string { return 'Laporan Pembelian'; }

    protected function getHeaderActions(): array { return []; }

    public function getPeriodeLabel(): string
    {
        $bulanNama = [
            '01'=>'Januari',  '02'=>'Februari', '03'=>'Maret',
            '04'=>'April',    '05'=>'Mei',       '06'=>'Juni',
            '07'=>'Juli',     '08'=>'Agustus',   '09'=>'September',
            '10'=>'Oktober',  '11'=>'November',  '12'=>'Desember',
        ];
        return ($bulanNama[$this->bulan] ?? '-') . ' ' . $this->tahun;
    }

    public function getVendorOptions(): \Illuminate\Support\Collection
    {
        return Vendor::orderBy('nama_vendor')->get(['id', 'nama_vendor']);
    }

    public function getLaporanRows(): \Illuminate\Support\Collection
    {
        $query = Pembelian::with(['vendor', 'details.barang'])
            ->orderBy('tanggal')
            ->orderBy('nomor');

        if ($this->bulan && $this->tahun) {
            $query->whereMonth('tanggal', $this->bulan)
                  ->whereYear('tanggal', $this->tahun);
        }

        if ($this->vendor_id) {
            $query->where('vendor_id', $this->vendor_id);
        }

        $rows = collect();

        foreach ($query->get() as $pb) {
            if ($pb->details->isEmpty()) {
                $rows->push([
                    'tanggal'      => $pb->tanggal,
                    'nomor'        => $pb->nomor,
                    'nama_vendor'  => $pb->vendor->nama_vendor ?? '-',
                    'kode_barang'  => '-',
                    'nama_barang'  => '-',
                    'qty'          => 0,
                    'harga_satuan' => 0,
                    'total_biaya'  => (float) $pb->total_akhir,
                    'status'       => $pb->status,
                ]);
            } else {
                foreach ($pb->details as $detail) {
                    $rows->push([
                        'tanggal'      => $pb->tanggal,
                        'nomor'        => $pb->nomor,
                        'nama_vendor'  => $pb->vendor->nama_vendor ?? '-',
                        'kode_barang'  => $detail->barang->kode_barang ?? '-',
                        'nama_barang'  => $detail->barang->nama_barang ?? '-',
                        'qty'          => (int) $detail->qty,
                        'harga_satuan' => (float) $detail->harga,
                        'total_biaya'  => (float) ($detail->subtotal ?: ($detail->qty * $detail->harga)),
                        'status'       => $pb->status,
                    ]);
                }
            }
        }

        return $rows;
    }

    public function getGrandTotal(\Illuminate\Support\Collection $rows): float
    {
        return $rows->sum('total_biaya');
    }
}
