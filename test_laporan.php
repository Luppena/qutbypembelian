<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tglMulai = \Carbon\Carbon::createFromDate(2026, 1, 1)->startOfMonth();
$tglAkhir = $tglMulai->copy()->endOfMonth();

$semuaBarang = \App\Models\Barang::orderBy('nama_barang')->get();
foreach ($semuaBarang as $barang) {
    $allHist = \App\Models\KartuStok::where('barang_id', $barang->id)
        ->where('tanggal', '<=', $tglAkhir->format('Y-m-d'))
        ->orderBy('tanggal', 'asc')
        ->orderBy('id', 'asc')
        ->get();
        
    echo "Barang ID: {$barang->id} ({$barang->nama_barang}) -> Hist count: " . $allHist->count() . "\n";
}
