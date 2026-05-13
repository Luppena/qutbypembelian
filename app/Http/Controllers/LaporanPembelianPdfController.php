<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use ZipArchive;

class LaporanPembelianPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $bulan    = $request->input('bulan', now()->format('m'));
        $tahun    = $request->input('tahun', now()->format('Y'));
        $vendorId = $request->input('vendor_id', '');
        $rows = $this->buildRows($bulan, $tahun, $vendorId);
        $grandTotal = $rows->sum('total');

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

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Laporan-Pembelian-' . $periodeLabel . '.pdf';

        if ($request->input('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function excel(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));
        $vendorId = $request->input('vendor_id', '');

        $rows = $this->buildRows($bulan, $tahun, $vendorId);
        $periodeLabel = $this->getPeriodeLabel($bulan, $tahun);
        $filename = 'Laporan-Pembelian-' . str_replace(' ', '-', $periodeLabel) . '.xlsx';
        $content = $this->buildXlsx($rows, $periodeLabel);

        return Response::make($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildRows(string $bulan, string $tahun, string $vendorId = ''): Collection
    {
        return PembelianDetail::with([
                'barang',
                'pembelian.vendor',
                'pembelian.details.grnDetails.grn',
                'pembelian.fakturPembelian.pembayarans',
            ])
            ->whereHas('pembelian', function ($query) use ($bulan, $tahun, $vendorId) {
                $query->whereMonth('tanggal', $bulan)
                    ->whereYear('tanggal', $tahun);

                if ($vendorId) {
                    $query->where('vendor_id', $vendorId);
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

    private function isDiterima(Pembelian $pembelian): bool
    {
        if ($pembelian->status === 'dibatalkan' || $pembelian->details->isEmpty()) {
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

    private function getPeriodeLabel(string $bulan, string $tahun): string
    {
        $bulanNama = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
            '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
            '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember',
        ];

        return ($bulanNama[$bulan] ?? '-') . ' ' . $tahun;
    }

    private function buildXlsx(Collection $rows, string $periodeLabel): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sheetRows = [
            ['CV QUTBY CREATIVINDO'],
            ['Laporan Pembelian'],
            ['Periode: ' . $periodeLabel],
            [],
            ['Tanggal Pembelian', 'Nama Barang', 'Jumlah', 'Harga Satuan', 'Total'],
        ];

        foreach ($rows as $row) {
            $sheetRows[] = [
                $row['tanggal'] ? \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') : '-',
                $row['nama_barang'],
                $row['jumlah'],
                $row['harga_satuan'],
                $row['total'],
            ];
        }

        $sheetRows[] = ['Total Pembelian Bulanan:', '', '', '', $rows->sum('total')];

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Pembelian" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildSheetXml($sheetRows));
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return $content;
    }

    private function buildSheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="' . ($rowIndex + 1) . '">';

            foreach ($row as $colIndex => $value) {
                $cellRef = chr(65 + $colIndex) . ($rowIndex + 1);

                if (is_numeric($value)) {
                    $xml .= '<c r="' . $cellRef . '"><v>' . $value . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $value, ENT_XML1) . '</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }
}
