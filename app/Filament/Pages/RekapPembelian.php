<?php

namespace App\Filament\Pages;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Vendor;
use Filament\Pages\Page;
use App\Filament\Traits\HasRoleAccess;

class RekapPembelian extends Page
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Pembelian';
    protected static ?string $title           = 'Laporan Pembelian';
    protected static ?int    $navigationSort  = 1;

    protected string $view = 'filament.pages.rekap-pembelian';

    public string $bulan     = '';
    public string $tahun     = '';
    public string $vendor_id = '';

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    public function getTitle(): string     { return 'Laporan Pembelian'; }
    public function getHeading(): string   { return 'Laporan Pembelian'; }
    public function getBreadcrumb(): string { return 'Laporan Pembelian'; }

    protected function getHeaderActions(): array { return []; }

    public function getPeriodeLabel(): string
    {
        $bulanNama = [
            '01' => 'Januari',  '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober',  '11' => 'November',  '12' => 'Desember',
        ];
        return ($bulanNama[$this->bulan] ?? '-') . ' ' . $this->tahun;
    }

    public function getVendorOptions(): \Illuminate\Support\Collection
    {
        return Vendor::orderBy('nama_vendor')->get(['id', 'nama_vendor']);
    }

    public function getStatusOptions(): array
    {
        return [
            ''           => 'Semua Status',
            'menunggu'   => 'Menunggu',
            'partial'    => 'Partial',
            'selesai'    => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    public function getRekapRows(): \Illuminate\Support\Collection
    {
        return $this->getLaporanRows();
    }

    public function getLaporanRows(): \Illuminate\Support\Collection
    {
        return PembelianDetail::with([
                'barang',
                'pembelian.vendor',
                'pembelian.details.grnDetails.grn',
                'pembelian.fakturPembelian.pembayarans',
            ])
            ->whereHas('pembelian', function ($query) {
                if ($this->bulan && $this->tahun) {
                    $query->whereMonth('tanggal', $this->bulan)
                        ->whereYear('tanggal', $this->tahun);
                }

                if ($this->vendor_id) {
                    $query->where('vendor_id', $this->vendor_id);
                }
            })
            ->get()
            ->filter(fn (PembelianDetail $detail) => $detail->pembelian
                && $this->isDiterima($detail->pembelian)
                && $this->isLunas($detail->pembelian))
            ->sortBy(fn (PembelianDetail $detail) => (optional($detail->pembelian->tanggal)->format('Y-m-d') ?? '') . '-' . str_pad((string) $detail->id, 10, '0', STR_PAD_LEFT))
            ->map(function (PembelianDetail $detail) {
                $hargaSatuan = (float) ($detail->harga_satuan ?? $detail->harga ?? $detail->hpp ?? 0);
                $jumlah = (int) ($detail->qty ?? 0);

                return [
                    'tanggal' => $detail->pembelian->tanggal,
                    'nama_barang' => $detail->barang->nama_barang ?? '-',
                    'jumlah' => $jumlah,
                    'harga_satuan' => $hargaSatuan,
                    'total' => $jumlah * $hargaSatuan,
                ];
            })
            ->values();
    }

    public function getGrandTotal(): float
    {
        return $this->getRekapRows()
            ->sum('total');
    }

    private function isDiterima(Pembelian $pembelian): bool
    {
        if ($pembelian->status === 'dibatalkan') {
            return false;
        }

        if ($pembelian->details->isEmpty()) {
            return false;
        }

        return $pembelian->details->every(
            fn ($detail) => in_array($detail->status_penerimaan, ['diterima_lengkap', 'over_quantity'], true)
        );
    }

    private function isLunas(Pembelian $pembelian): bool
    {
        if ($pembelian->status === 'lunas') {
            return true;
        }

        $faktur = $pembelian->fakturPembelian;

        if (! $faktur || $faktur->pembayarans->isEmpty()) {
            return false;
        }

        $totalTagihan = (float) ($pembelian->total_akhir ?? $faktur->total_netto ?? 0);
        $totalBayar = (float) $faktur->pembayarans->sum('nilai_pembayaran');

        return $totalTagihan > 0 && $totalBayar >= $totalTagihan;
    }

    public function getPdfUrl(): string
    {
        return route('laporan-pembelian.pdf', [
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'vendor_id' => $this->vendor_id,
        ]);
    }

    public function getExcelUrl(): string
    {
        return route('laporan-pembelian.excel', [
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'vendor_id' => $this->vendor_id,
        ]);
    }
}
