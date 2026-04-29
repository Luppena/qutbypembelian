@extends('filament::layouts.app')

@section('content')
    <div class="filament-content">
        <h2>Detail Pembelian - {{ $pembelian->nomor }}</h2>

        <div class="grid grid-cols-1 gap-4 mt-4">
            <div class="p-4 border rounded-lg shadow-sm">
                <h3 class="font-semibold">Informasi Pembelian</h3>
                <ul>
                    <li><strong>ID Pemesanan:</strong> {{ $pembelian->nomor }}</li>
                    <li><strong>Tanggal:</strong> {{ $pembelian->tanggal }}</li>
                    <li><strong>Vendor:</strong> {{ $pembelian->vendor->nama_vendor }}</li>
                    <li><strong>Total Akhir:</strong> {{ number_format($pembelian->total_netto, 2) }}</li>
                    <li><strong>Status:</strong> {{ $pembelian->status }}</li>
                </ul>
            </div>

            <div class="p-4 border rounded-lg shadow-sm">
                <h3 class="font-semibold">Rincian Barang</h3>
                <table class="min-w-full table-auto">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border">Barang</th>
                            <th class="px-4 py-2 border">Qty</th>
                            <th class="px-4 py-2 border">Harga Satuan</th>
                            <th class="px-4 py-2 border">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembelian->detail as $item)
                            <tr>
                                <td class="px-4 py-2 border">{{ $item->barang->nama_barang }}</td>
                                <td class="px-4 py-2 border">{{ $item->qty }}</td>
                                <td class="px-4 py-2 border">{{ number_format($item->harga_satuan, 2) }}</td>
                                <td class="px-4 py-2 border">{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
