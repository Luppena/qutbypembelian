<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Utang - {{ $vendor->nama_vendor }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin-top: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .info-table { border: none; margin-bottom: 15px; width: 100%; }
        .info-table td { padding: 2px 0; }
        .info-title { font-weight: bold; width: 120px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .data-table th { background-color: #f3f4f6; color: #1f2937; font-weight: bold; text-align: center; font-size: 11px; }
        .data-table td { font-size: 10px; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        .bg-totals { background-color: #f9fafb; }
    </style>
</head>
<body>

    <div class="header">
        <h2>KARTU UTANG</h2>
        <p>Bulan: {{ $periodeLabel }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-title">Nama Kreditor</td>
            <td>: {{ $vendor->nama_vendor }}</td>
        </tr>
        <tr>
            <td class="info-title">Nomer Rekening</td>
            <td>: {{ $akunLabel }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Ref</th>
                <th>Debet</th>
                <th>Kredit</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $tglMulai->translatedFormat('d F Y') }}</td>
                <td>Saldo Awal</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-right">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
            </tr>
            @foreach($rows as $row)
            <tr>
                <td class="text-center">{{ $row['tanggal'] }}</td>
                <td>{{ $row['keterangan'] }}</td>
                <td class="text-center">{{ $row['ref'] }}</td>
                <td class="text-right">{{ $row['debet'] > 0 ? 'Rp ' . number_format($row['debet'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">Rp {{ number_format($row['saldo'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="bg-totals font-bold">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">Rp {{ number_format($totalDebet, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
