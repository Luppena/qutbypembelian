<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanPembelianPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $bulan    = $request->input('bulan', now()->format('m'));
        $tahun    = $request->input('tahun', now()->format('Y'));
        $vendorId = $request->input('vendor_id', '');
        $status   = $request->input('status', '');

        $query = Pembelian::with(['vendor', 'details.barang'])
            ->orderBy('tanggal')
            ->orderBy('nomor');

        if ($bulan && $tahun) {
            $query->whereMonth('tanggal', $bulan)
                  ->whereYear('tanggal', $tahun);
        }

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if ($status) {
            if ($status === 'lunas') {
                $query->where('status', 'lunas');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhere('status', '!=', 'lunas');
                });
            }
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

        $grandTotal = $rows->sum('total_biaya');

        $bulanNama = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
            '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
            '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember',
        ];
        $periodeLabel = ($bulanNama[$bulan] ?? '-') . ' ' . $tahun;

        $vendorNama = '';
        if ($vendorId) {
            $vendorNama = Vendor::find($vendorId)?->nama_vendor ?? '';
        }

        $pdf = Pdf::loadView('pdf.laporan-pembelian', [
            'rows'         => $rows,
            'grandTotal'   => $grandTotal,
            'periodeLabel' => $periodeLabel,
            'vendorNama'   => $vendorNama,
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = 'Laporan-Pembelian-' . $periodeLabel . '.pdf';

        if ($request->input('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
