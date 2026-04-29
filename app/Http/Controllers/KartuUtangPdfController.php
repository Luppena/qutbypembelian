<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Pembelian;
use App\Models\PembayaranPembelian;
use App\Models\DaftarAkun;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class KartuUtangPdfController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));
        $vendor_id = $request->input('vendor_id');

        if (!$vendor_id) {
            abort(404, 'Vendor tidak ditentukan');
        }

        $vendor = Vendor::find($vendor_id);
        if (!$vendor) {
            abort(404, 'Vendor tidak ditemukan');
        }

        $data = $this->getReportData($vendor, $bulan, $tahun);

        $pdf = Pdf::loadView('pdf.kartu-utang', $data);
        $pdf->setPaper('A4', 'landscape');

        $periodeLabel = $data['periodeLabel'];
        $filename = 'Kartu-Utang-' . str_replace(' ', '-', $vendor->nama_vendor) . '-' . str_replace(' ', '-', $periodeLabel) . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    private function getReportData(Vendor $vendor, string $bulan, string $tahun): array
    {
        $tglMulai = Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->startOfMonth();
        $tglAkhir = $tglMulai->copy()->endOfMonth();
        $vendor_id = $vendor->id;

        // Saldo Awal (sebelum tglMulai)
        $historisKredit = (float) Pembelian::query()
            ->where('vendor_id', $vendor_id)
            ->where('tanggal', '<', $tglMulai->format('Y-m-d'))
            ->sum('total_akhir');

        $historisDebet = (float) PembayaranPembelian::query()
            ->where('vendor_id', $vendor_id)
            ->where('tanggal_pembayaran', '<', $tglMulai->format('Y-m-d'))
            ->sum('nilai_pembayaran');

        $saldoAwal = $historisKredit - $historisDebet;

        // Mutasi
        $mutasi = [];

        // 1. Pembelian (Kredit)
        $pembelians = Pembelian::query()->with('vendor')
            ->where('vendor_id', $vendor_id)
            ->whereBetween('tanggal', [$tglMulai->format('Y-m-d'), $tglAkhir->format('Y-m-d')])
            ->get();
        
        foreach ($pembelians as $p) {
            $mutasi[] = [
                'tanggal' => Carbon::parse($p->tanggal),
                'keterangan' => 'Pembelian dari ' . ($p->vendor?->nama_vendor ?? '-'),
                'ref' => $p->nomor,
                'debet' => 0.0,
                'kredit' => (float)$p->total_akhir,
            ];
        }

        // 2. Pembayaran (Debet)
        $pembayarans = PembayaranPembelian::query()
            ->where('vendor_id', $vendor_id)
            ->whereBetween('tanggal_pembayaran', [$tglMulai->format('Y-m-d'), $tglAkhir->format('Y-m-d')])
            ->get();
            
        foreach ($pembayarans as $pY) {
            $mutasi[] = [
                'tanggal' => Carbon::parse($pY->tanggal_pembayaran),
                'keterangan' => 'Pembayaran Utang',
                'ref' => 'PAY-PB-' . $pY->id,
                'debet' => (float)$pY->nilai_pembayaran,
                'kredit' => 0.0,
            ];
        }

        // Sort by timestamp
        usort($mutasi, function($a, $b) {
            return $a['tanggal']->timestamp <=> $b['tanggal']->timestamp;
        });

        // Hitung Running Balance
        $rows = [];
        $running = $saldoAwal;
        $totalDebet = 0;
        $totalKredit = 0;

        foreach ($mutasi as $m) {
            $debet = $m['debet'];
            $kredit = $m['kredit'];
            
            $running += $kredit - $debet;
            $totalDebet += $debet;
            $totalKredit += $kredit;
            
            $rows[] = [
                'tanggal' => $m['tanggal']->translatedFormat('d F Y'),
                'keterangan' => $m['keterangan'],
                'ref' => $m['ref'],
                'debet' => $debet,
                'kredit' => $kredit,
                'saldo' => $running,
            ];
        }

        $akun = DaftarAkun::query()->where('kode_akun', '211')->first();
        $akunLabel = $akun ? ($akun->kode_akun . ' - ' . $akun->nama_akun) : '211 - Utang Usaha';

        $namaBulan = [
            '01' => 'Januari',  '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober',  '11' => 'November',  '12' => 'Desember',
        ];
        
        $periodeLabel = ($namaBulan[$bulan] ?? '-') . ' ' . $tahun;

        return [
            'vendor' => $vendor,
            'periodeLabel' => $periodeLabel,
            'akunLabel' => $akunLabel,
            'saldoAwal' => $saldoAwal,
            'rows' => $rows,
            'totalDebet' => $totalDebet,
            'totalKredit' => $totalKredit,
            'saldoAkhir' => $running,
            'tglMulai' => $tglMulai,
        ];
    }
}
