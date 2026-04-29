<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KartuStokService;
use Barryvdh\DomPDF\Facade\Pdf;

class KartuStokPdfController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        $data = app(KartuStokService::class)->getLaporanData($bulan, $tahun);

        $namaBulan = [
            '01' => 'Januari',  '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober',  '11' => 'November',  '12' => 'Desember',
        ];
        $periodeLabel = ($namaBulan[$bulan] ?? '-') . ' ' . $tahun;

        $pdf = Pdf::loadView('pdf.kartu-stok', [
            'data'         => $data,
            'periodeLabel' => $periodeLabel,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = 'Laporan-Kartu-Stok-' . str_replace(' ', '-', $periodeLabel) . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
