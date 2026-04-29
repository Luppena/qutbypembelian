<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COUNT ===\n";
echo "faktur_pembelian_details: " . DB::table('faktur_pembelian_details')->count() . "\n";
echo "penjualan_detail: " . DB::table('penjualan_detail')->count() . "\n";
echo "kartu_stok: " . DB::table('kartu_stok')->count() . "\n";
echo "kartu_stok is_saldo_awal=1: " . DB::table('kartu_stok')->where('is_saldo_awal', 1)->count() . "\n";

echo "\n=== SAMPLE faktur_pembelian_details (join) ===\n";
$rows = DB::table('faktur_pembelian_details')
    ->join('faktur_pembelians', 'faktur_pembelians.id', '=', 'faktur_pembelian_details.faktur_pembelian_id')
    ->select('faktur_pembelians.tanggal_faktur', 'faktur_pembelian_details.barang_id', 'faktur_pembelian_details.qty', 'faktur_pembelian_details.harga')
    ->limit(5)
    ->get();
foreach ($rows as $r) {
    echo "  tanggal={$r->tanggal_faktur}, barang_id={$r->barang_id}, qty={$r->qty}, harga={$r->harga}\n";
}

echo "\n=== SAMPLE penjualan_detail (join) ===\n";
$rows2 = DB::table('penjualan_detail')
    ->join('penjualan', 'penjualan.id', '=', 'penjualan_detail.penjualan_id')
    ->select('penjualan.tanggal_faktur', 'penjualan_detail.barang_id', 'penjualan_detail.qty')
    ->limit(5)
    ->get();
foreach ($rows2 as $r) {
    echo "  tanggal={$r->tanggal_faktur}, barang_id={$r->barang_id}, qty={$r->qty}\n";
}

echo "\n=== SAMPLE kartu_stok ===\n";
$rows3 = DB::table('kartu_stok')->limit(5)->get();
foreach ($rows3 as $r) {
    echo "  id={$r->id}, barang_id=" . ($r->barang_id ?? 'null') . ", is_saldo_awal=" . ($r->is_saldo_awal ?? 'null') . ", masuk={$r->masuk}, keluar={$r->keluar}, tanggal={$r->tanggal}\n";
}
