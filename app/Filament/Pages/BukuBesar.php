<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\DaftarAkun;
use App\Models\JurnalDetail;
use Carbon\Carbon;
use App\Filament\Traits\HasRoleAccess;

class BukuBesar extends Page
{
    use HasRoleAccess;

    protected static array $allowedRoles = ['finance'];
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null   $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $title           = 'Buku Besar';
    protected string $view = 'filament.pages.buku-besar';

    // Filter state
    public string $bulan = '';
    public string $tahun = '';
    public ?int   $daftar_akun_id = null;

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    public function getTitle(): string { return 'Buku Besar'; }
    public function getHeading(): string { return 'Buku Besar'; }
    public function getBreadcrumb(): string { return 'Buku Besar'; }

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

    /**
     * Ambil daftar akun yang punya transaksi pada periode ini
     * (atau semua jika tidak ada filter akun).
     */
    public function getLedger(): array
    {
        $tglMulai = Carbon::createFromDate($this->tahun, $this->bulan, 1)->startOfMonth();
        $tglAkhir = $tglMulai->copy()->endOfMonth();

        // Tentukan akun yang akan ditampilkan
        $akunQuery = DaftarAkun::orderBy('kode_akun');
        if ($this->daftar_akun_id) {
            $akunQuery->where('id', $this->daftar_akun_id);
        }
        $akunList = $akunQuery->get();

        $result = [];

        foreach ($akunList as $akun) {
            // Saldo awal = saldo awal nominal + semua mutasi sebelum bulan ini
            $historisDebit  = JurnalDetail::where('daftar_akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->where('tanggal', '<', $tglMulai))
                ->sum('debit');
            $historisKredit = JurnalDetail::where('daftar_akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->where('tanggal', '<', $tglMulai))
                ->sum('kredit');

            $saldoNormal = strtolower($akun->saldo_normal ?? 'debit');
            $saldoAwal   = ($akun->saldo_awal_nominal ?? 0);

            if ($saldoNormal === 'debit') {
                $saldoAwal += ($historisDebit - $historisKredit);
            } else {
                $saldoAwal += ($historisKredit - $historisDebit);
            }

            // Ambil mutasi bulan ini
            $mutasi = JurnalDetail::with('jurnal')
                ->where('daftar_akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereBetween('tanggal', [$tglMulai, $tglAkhir]))
                ->get()
                ->sortBy('jurnal.tanggal');

            // Hanya tampilkan akun yang punya saldo awal != 0 ATAU ada mutasi
            if ($mutasi->isEmpty() && $saldoAwal == 0) {
                continue;
            }

            // Hitung running balance per baris
            $rows    = [];
            $running = $saldoAwal;
            foreach ($mutasi as $m) {
                if ($saldoNormal === 'debit') {
                    $running += ($m->debit - $m->kredit);
                } else {
                    $running += ($m->kredit - $m->debit);
                }
                $rows[] = [
                    'tanggal'     => $m->jurnal->tanggal,
                    'keterangan'  => $m->keterangan ?: ($m->jurnal->keterangan ?? '-'),
                    'referensi'   => $m->jurnal->referensi ?? '',
                    'debit'       => $m->debit,
                    'kredit'      => $m->kredit,
                    'saldo'       => $running,
                ];
            }

            $result[] = [
                'akun'       => $akun,
                'saldo_awal' => $saldoAwal,
                'rows'       => $rows,
                'total_debit'  => $mutasi->sum('debit'),
                'total_kredit' => $mutasi->sum('kredit'),
                'saldo_akhir'  => $running ?? $saldoAwal,
            ];
        }

        return $result;
    }

    public function getDaftarAkunOptions(): array
    {
        return DaftarAkun::orderBy('kode_akun')
            ->get()
            ->mapWithKeys(fn($a) => [$a->id => $a->kode_akun . ' - ' . $a->nama_akun])
            ->toArray();
    }
}
