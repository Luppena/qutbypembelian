<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Vendor;
use App\Models\Pembelian;
use App\Models\PembayaranPembelian;
use App\Models\DaftarAkun;
use Carbon\Carbon;
use App\Filament\Traits\HasRoleAccess;

class KartuUtangPage extends Page
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Kartu Utang';
    protected static ?string $title           = 'Kartu Utang';
    protected string $view = 'filament.pages.kartu-utang';

    public string $bulan = '';
    public string $tahun = '';
    public ?int   $vendor_id = null;

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    public function getTitle(): string { return 'Kartu Utang'; }
    public function getHeading(): string { return 'Kartu Utang'; }
    public function getBreadcrumb(): string { return 'Kartu Utang'; }

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

    public function getAkunUtangLabel(): string
    {
        $akun = DaftarAkun::where('kode_akun', '211')->first();
        return $akun ? ($akun->kode_akun . ' - ' . $akun->nama_akun) : '211 - Utang Usaha';
    }

    public function getVendorOptions(): array
    {
        return Vendor::orderBy('nama_vendor')
            ->get()
            ->mapWithKeys(fn($v) => [$v->id => $v->nama_vendor])
            ->toArray();
    }

    public function getSelectedVendor()
    {
        return $this->vendor_id ? Vendor::find($this->vendor_id) : null;
    }

    public function cetakPdf(): void
    {
        if (!$this->vendor_id) {
            \Filament\Notifications\Notification::make()->title('Pilih Supplier terlebih dahulu')->warning()->send();
            return;
        }

        $params = http_build_query([
            'bulan'     => $this->bulan,
            'tahun'     => $this->tahun,
            'vendor_id' => $this->vendor_id,
        ]);

        $url = route('kartu-utang.pdf') . '?' . $params;
        $this->js("window.open('{$url}', '_blank')");
    }

    public function unduhPdf(): void
    {
        if (!$this->vendor_id) {
            \Filament\Notifications\Notification::make()->title('Pilih Supplier terlebih dahulu')->warning()->send();
            return;
        }

        $params = http_build_query([
            'bulan'     => $this->bulan,
            'tahun'     => $this->tahun,
            'vendor_id' => $this->vendor_id,
            'download'  => '1',
        ]);

        $url = route('kartu-utang.pdf') . '?' . $params;
        $this->js("window.open('{$url}', '_blank')");
    }

    public function getLaporanData(): array
    {
        if (!$this->vendor_id) {
            return [];
        }

        $tglMulai = Carbon::createFromDate($this->tahun, $this->bulan, 1)->startOfMonth();
        $tglAkhir = $tglMulai->copy()->endOfMonth();

        // Saldo Awal (sebelum tglMulai)
        // Penambahan Utang = Pembelian (Kredit via Pembelian total_akhir)
        $historisKredit = Pembelian::where('vendor_id', $this->vendor_id)
            ->where('tanggal', '<', $tglMulai->format('Y-m-d'))
            ->sum('total_akhir');

        // Pengurangan Utang = Pembayaran Pembelian (Debet via nilai_pembayaran)
        $historisDebet = PembayaranPembelian::where('vendor_id', $this->vendor_id)
            ->where('tanggal_pembayaran', '<', $tglMulai->format('Y-m-d'))
            ->sum('nilai_pembayaran');

        $saldoAwal = $historisKredit - $historisDebet;

        // Transaksi Bulan Ini
        $mutasi = collect([]);

        // 1. Pembelian (Kredit)
        $pembelians = Pembelian::with('vendor')
            ->where('vendor_id', $this->vendor_id)
            ->whereBetween('tanggal', [$tglMulai->format('Y-m-d'), $tglAkhir->format('Y-m-d')])
            ->get();
        foreach ($pembelians as $p) {
            $keterangan = 'Pembelian dari ' . ($p->vendor?->nama_vendor ?? '-');

            $mutasi->push([
                'tanggal' => $p->tanggal,
                'keterangan' => $keterangan,
                'ref' => $p->nomor,
                'debet' => 0,
                'kredit' => (float)$p->total_akhir,
            ]);
        }

        // 2. Pembayaran (Debet)
        $pembayarans = PembayaranPembelian::where('vendor_id', $this->vendor_id)
            ->whereBetween('tanggal_pembayaran', [$tglMulai->format('Y-m-d'), $tglAkhir->format('Y-m-d')])
            ->get();
        foreach ($pembayarans as $pY) {
             // Generate reference if not available
            $ref = 'PAY-PB-'.$pY->id;
            $mutasi->push([
                'tanggal' => Carbon::parse($pY->tanggal_pembayaran),
                'keterangan' => 'Pembayaran Utang',
                'ref' => $ref,
                'debet' => (float)$pY->nilai_pembayaran,
                'kredit' => 0,
            ]);
        }

        // Sort transaksi berdasarkan tanggal
        $mutasi = $mutasi->sortBy(function($m) {
            return $m['tanggal']->timestamp;
        });

        // Hitung Running Balance
        $rows = [];
        $running = $saldoAwal;
        foreach ($mutasi as $m) {
            // Utang bertambah jika Kredit, berkurang jika Debet
            $running += $m['kredit'] - $m['debet'];
            $rows[] = [
                'tanggal' => $m['tanggal']->translatedFormat('d F Y'),
                'keterangan' => $m['keterangan'],
                'ref' => $m['ref'],
                'debet' => $m['debet'],
                'kredit' => $m['kredit'],
                'saldo' => $running,
            ];
        }

        return [
            'vendor' => $this->getSelectedVendor(),
            'saldo_awal' => $saldoAwal,
            'rows' => $rows,
            'total_debet' => $mutasi->sum('debet'),
            'total_kredit' => $mutasi->sum('kredit'),
            'saldo_akhir' => $running,
        ];
    }
}
