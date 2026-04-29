<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian - {{ $periodeLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1f2937; }

        .header { text-align: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #1d4ed8; }
        .header h1 { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #111827; }
        .header h2 { font-size: 13px; font-weight: 700; color: #1d4ed8; margin-top: 4px; }
        .header p { font-size: 11px; color: #6b7280; margin-top: 3px; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead tr { background-color: #1d4ed8; color: #fff; }
        th { padding: 7px 6px; text-align: center; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #1e40af; }
        td { padding: 6px 6px; border: 1px solid #d1d5db; font-size: 10px; }

        tbody tr:nth-child(even) { background-color: #f9fafb; }
        tbody tr:nth-child(odd) { background-color: #ffffff; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .font-bold { font-weight: 700; }

        .badge { display: inline-block; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }
        .badge-lunas { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

        .po-badge { background: #eff6ff; padding: 1px 5px; border-radius: 3px; font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; font-weight: 700; color: #1d4ed8; }

        tfoot tr { background-color: #eff6ff; }
        tfoot td { padding: 8px 6px; border: 1px solid #93c5fd; font-weight: 700; }

        .rp { color: #9ca3af; font-size: 8px; margin-right: 1px; }

        .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CV Qutby Creativindo</h1>
        <h2>LAPORAN PEMBELIAN BARANG</h2>
        <p>Periode: {{ $periodeLabel }}
            @if($vendorNama)
                &mdash; Vendor: {{ $vendorNama }}
            @endif
        </p>
    </div>

    @if($rows->isEmpty())
        <div style="text-align:center; padding: 40px 0; color: #9ca3af;">
            <p style="font-size: 14px; font-weight: 700;">Belum Ada Data</p>
            <p style="font-size: 11px; margin-top: 4px;">Tidak ada transaksi pembelian untuk periode ini.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:4%">No</th>
                    <th style="width:10%">Tanggal</th>
                    <th style="width:12%">No. PO</th>
                    <th style="width:17%">Nama Vendor</th>
                    <th style="width:10%">Kode Barang</th>
                    <th style="width:19%">Nama Barang</th>
                    <th style="width:5%">Qty</th>
                    <th style="width:11%">Harga Satuan</th>
                    <th style="width:12%">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                    <tr>
                        <td class="text-center" style="color:#9ca3af; font-size:9px;">{{ $i + 1 }}</td>
                        <td class="text-center" style="color:#6b7280; white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d') }}
                        </td>
                        <td class="text-center">
                            <span class="po-badge">{{ $row['nomor'] }}</span>
                        </td>
                        <td class="text-left font-bold" style="color:#1f2937;">{{ $row['nama_vendor'] }}</td>
                        <td class="text-center font-mono" style="font-size:9px; color:#374151;">{{ $row['kode_barang'] }}</td>
                        <td class="text-left" style="color:#374151;">{{ $row['nama_barang'] }}</td>
                        <td class="text-center font-bold">{{ $row['qty'] > 0 ? number_format($row['qty']) : '-' }}</td>
                        <td class="text-right">
                            @if($row['harga_satuan'] > 0)
                                <span class="rp">Rp</span>
                                <span class="font-mono">{{ number_format($row['harga_satuan'], 0, ',', '.') }}</span>
                            @else
                                <span style="color:#d1d5db">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="rp">Rp</span>
                            <span class="font-mono font-bold">{{ number_format($row['total_biaya'], 0, ',', '.') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" class="text-right" style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#374151;">
                        TOTAL:
                    </td>
                    <td class="text-right">
                        <span class="rp">Rp</span>
                        <span class="font-mono" style="font-size:12px; font-weight:800; color:#1d4ed8;">{{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
