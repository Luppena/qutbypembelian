<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian - {{ $periodeLabel }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        h1 { margin: 0; font-size: 17px; font-weight: 800; }
        h2 { margin: 4px 0 0; font-size: 15px; font-weight: 700; }
        .periode { margin: 8px 0 18px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111827; padding: 8px; }
        th { background: #ffffff; font-weight: 700; text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
    </style>
</head>
<body>
    <h1>CV QUTBY CREATIVINDO</h1>
    <h2>Laporan Pembelian</h2>
    <div class="periode">
        Periode: {{ $periodeLabel }}
        @if($vendorNama)
            | Vendor: {{ $vendorNama }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal Pembelian</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['tanggal'] ? \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') : '-' }}</td>
                    <td>{{ $row['nama_barang'] }}</td>
                    <td class="text-right">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($row['harga_satuan'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 24px;">
                        Tidak ada data pembelian yang sudah diterima dan lunas pada periode {{ $periodeLabel }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="font-bold">Total Pembelian Bulanan:</td>
                    <td class="text-right font-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
