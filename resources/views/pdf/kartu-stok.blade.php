<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Stok - {{ $periodeLabel }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .company { font-size: 14px; font-weight: bold; margin: 5px 0 0 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .footer { margin-top: 20px; font-size: 10px; color: #555; }
    </style>
</head>
<body>

    <div class="header">
        <p class="company">CV QUTBY CREATIVINDO</p>
        <p class="title">LAPORAN KARTU STOK (FIFO)</p>
        <p style="margin: 5px 0 0 0;">Periode: {{ $periodeLabel }}</p>
    </div>

    @php
        $totSaldoAwalQty   = collect($data)->sum('saldo_awal_qty');
        $totSaldoAwalNilai = collect($data)->sum('saldo_awal_nilai');
        $totMasukQty       = collect($data)->sum('masuk_qty');
        $totMasukNilai     = collect($data)->sum('masuk_nilai');
        $totKeluarQty      = collect($data)->sum('keluar_qty');
        $totKeluarNilai    = collect($data)->sum('keluar_nilai');
        $totAkhirQty       = collect($data)->sum('saldo_akhir_qty');
        $totAkhirNilai     = collect($data)->sum('saldo_akhir_nilai');
    @endphp

    @if(empty($data))
        <p class="text-center" style="margin-top: 50px;">Tidak ada barang dengan saldo atau mutasi di periode ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Kode</th>
                    <th rowspan="2">Nama Barang</th>
                    <th colspan="2">Saldo Awal</th>
                    <th colspan="2">Masuk</th>
                    <th colspan="2">Keluar (HPP)</th>
                    <th colspan="2">Saldo Akhir</th>
                </tr>
                <tr>
                    <th>Qty</th>
                    <th>Nilai (Rp)</th>
                    <th>Qty</th>
                    <th>Nilai (Rp)</th>
                    <th>Qty</th>
                    <th>Nilai (Rp)</th>
                    <th>Qty</th>
                    <th>Nilai (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $row['barang']->kode_barang }}</td>
                        <td>{{ $row['barang']->nama_barang }}</td>

                        <td class="text-center">{{ $row['saldo_awal_qty'] > 0 ? number_format($row['saldo_awal_qty']) : '-' }}</td>
                        <td class="text-right">{{ $row['saldo_awal_nilai'] > 0 ? number_format($row['saldo_awal_nilai'], 0, ',', '.') : '-' }}</td>

                        <td class="text-center">{{ $row['masuk_qty'] > 0 ? number_format($row['masuk_qty']) : '-' }}</td>
                        <td class="text-right">{{ $row['masuk_nilai'] > 0 ? number_format($row['masuk_nilai'], 0, ',', '.') : '-' }}</td>

                        <td class="text-center">{{ $row['keluar_qty'] > 0 ? number_format($row['keluar_qty']) : '-' }}</td>
                        <td class="text-right">{{ $row['keluar_nilai'] > 0 ? number_format($row['keluar_nilai'], 0, ',', '.') : '-' }}</td>

                        <td class="text-center font-bold">{{ number_format($row['saldo_akhir_qty']) }}</td>
                        <td class="text-right font-bold">{{ number_format($row['saldo_akhir_nilai'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" class="text-center" style="text-transform: uppercase;">Total</td>
                    <td class="text-center">{{ number_format($totSaldoAwalQty) }}</td>
                    <td class="text-right">{{ number_format($totSaldoAwalNilai, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totMasukQty) }}</td>
                    <td class="text-right">{{ number_format($totMasukNilai, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totKeluarQty) }}</td>
                    <td class="text-right">{{ number_format($totKeluarNilai, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totAkhirQty) }}</td>
                    <td class="text-right">{{ number_format($totAkhirNilai, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>* Nilai menggunakan metode FIFO. Saldo Akhir = Saldo Awal + Masuk - Keluar.</p>
            <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    @endif

</body>
</html>
