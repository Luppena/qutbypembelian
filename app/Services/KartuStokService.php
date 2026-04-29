<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\KartuStok;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\StokFifoLayer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KartuStokService
{
    /**
     * Dijalankan dari PembelianObserver (created / updated)
     * STOK MASUK: dari Pembelian (langsung)
     */
    public function syncPembelian(Pembelian $pembelian, Collection $details): void
    {
        DB::transaction(function () use ($pembelian, $details) {
            // 1) Rollback jika edit
            $this->rollbackPembelian($pembelian->id);

            // 2) Insert baru
            foreach ($details as $d) {
                /** @var PembelianDetail $d */
                $barang = Barang::find($d->barang_id);
                if (!$barang) continue;

                $harga = (float) $d->harga;
                $qty = (int) $d->qty;

                // Tambah stok ke master barang
                $barang->increment('stok', $qty);
                $stokAkhir = (int) ($barang->fresh()->stok ?? 0);

                // Insert Layer FIFO
                $layer = StokFifoLayer::create([
                    'barang_id'      => $d->barang_id,
                    'tanggal'        => $pembelian->tanggal,
                    'source_type'    => 'pembelian',
                    'source_id'      => $pembelian->id,
                    'source_line_id' => $d->id,
                    'qty_masuk'      => $qty,
                    'qty_sisa'       => $qty,
                    'harga_unit'     => $harga,
                ]);

                // Insert Kartu Stok
                KartuStok::create([
                    'barang_id'      => $d->barang_id,
                    'tanggal'        => $pembelian->tanggal,
                    'is_saldo_awal'  => 0,
                    'keterangan'     => 'Pembelian ' . ($pembelian->nomor ?? '-'),
                    'masuk'          => $qty,
                    'keluar'         => 0,
                    'saldo'          => $stokAkhir,
                    'harga'          => $harga,
                    'hpp'            => 0,
                    'source_type'    => 'pembelian',
                    'source_id'      => $pembelian->id,
                    'source_line_id' => $d->id,
                ]);
            }
        });
    }

    public function rollbackPembelian(int $pembelianId): void
    {
        // Revert barang stok & delete fifo layer
        $layers = StokFifoLayer::where('source_type', 'pembelian')->where('source_id', $pembelianId)->get();
        foreach ($layers as $layer) {
            /** @var \App\Models\StokFifoLayer $layer */
            Barang::where('id', $layer->barang_id)->decrement('stok', $layer->qty_masuk);
            $layer->delete();
        }

        // Delete kartu_stok
        KartuStok::where('source_type', 'pembelian')->where('source_id', $pembelianId)->delete();
    }

    /**
     * Dijalankan dari PenjualanObserver (created/updated)
     * STOK KELUAR: dari Penjualan (mengkonsumsi FIFO)
     */
    public function syncPenjualan(Penjualan $penjualan, Collection $items): void
    {
        DB::transaction(function () use ($penjualan, $items) {
            // 1) Rollback jika edit
            $this->rollbackPenjualan($penjualan->id);

            $totalHppGlobal = 0;

            foreach ($items as $it) {
                /** @var PenjualanDetail $it */
                $barang = Barang::find($it->barang_id);
                if (!$barang) continue;

                $qtyDibutuhkan = (int) $it->qty;
                $keteranganMap = 'Penjualan ' . ($penjualan->no_faktur ?? '-');

                // Konsumsi FIFO layer
                $layers = StokFifoLayer::where('barang_id', $it->barang_id)
                    ->where('qty_sisa', '>', 0)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($layers as $layer) {
                    /** @var \App\Models\StokFifoLayer $layer */
                    if ($qtyDibutuhkan <= 0) break;

                    $qtyDiambil = min($qtyDibutuhkan, $layer->qty_sisa);
                    $hargaLayer = (float) $layer->harga_unit;

                    // Update sisa layer
                    $layer->decrement('qty_sisa', $qtyDiambil);
                    $qtyDibutuhkan -= $qtyDiambil;

                    $totalHppGlobal += ($qtyDiambil * $hargaLayer);

                    // Re-calculate stok (decremented real-time to log exact sequence balance)
                    $barang->decrement('stok', $qtyDiambil);
                    $stokSekarang = (int) ($barang->fresh()->stok ?? 0);

                    // Catat Kartu Stok (bisa multi row jika 1 order ambil dari 2 layer berbeda harganya)
                    KartuStok::create([
                        'tanggal'        => $penjualan->tanggal_faktur,
                        'is_saldo_awal'  => 0,
                        'keterangan'     => $keteranganMap . ' (HPP '.number_format($hargaLayer,0,',','.').')',
                        'masuk'          => 0,
                        'keluar'         => $qtyDiambil,
                        'saldo'          => $stokSekarang,
                        'harga'          => 0,
                        'hpp'            => $hargaLayer,
                        'source_type'    => 'penjualan',
                        'source_id'      => $penjualan->id,
                        'source_line_id' => $it->id, // track for rollback mapping
                    ]);
                }

                // Jika masih butuh (stok FIFO minus/habis) -- Fallback
                if ($qtyDibutuhkan > 0) {
                    $fallbackHpp = (float) ($barang->hpp_satuan ?? 0);
                    $totalHppGlobal += ($qtyDibutuhkan * $fallbackHpp);

                    $barang->decrement('stok', $qtyDibutuhkan);
                    $stokSekarang = (int) ($barang->fresh()->stok ?? 0);

                    KartuStok::create([
                        'tanggal'        => $penjualan->tanggal_faktur,
                        'is_saldo_awal'  => 0,
                        'keterangan'     => $keteranganMap . ' (Stok Kurang/Minus)',
                        'masuk'          => 0,
                        'keluar'         => $qtyDibutuhkan,
                        'saldo'          => $stokSekarang,
                        'harga'          => 0,
                        'hpp'            => $fallbackHpp,
                        'source_type'    => 'penjualan',
                        'source_id'      => $penjualan->id,
                        'source_line_id' => $it->id,
                    ]);
                }
            }

            // Update HPP Penjualan di header
            DB::table('penjualan')->where('id', $penjualan->id)->update([
                'total_hpp' => $totalHppGlobal,
                'updated_at' => now(),
            ]);
        });
    }

    public function rollbackPenjualan(int $penjualanId): void
    {
        // 1) Cari semua pemotongan kartu stok akibat penjualan ini
        $kartus = KartuStok::where('source_type', 'penjualan')->where('source_id', $penjualanId)->get();

        foreach ($kartus as $k) {
            /** @var \App\Models\KartuStok $k */
            $barang = Barang::find($k->barang_id);
            if (!$barang) continue;

            $qtyKembali = (int) $k->keluar;
            $hppKembali = (float) $k->hpp;

            // Kembalikan ke layer FIFO (cari layer mana yang harganya match & pernah keluar dari source ini?
            // Karena kita nggak track murni layer_id, pendekatan paling akurat: cari layer terbaru dgn harga itu yg sisa < masuk.
            // Atau cukup tambahkan stok_sisa di barang, lalu untuk FIFO layer, ya direstore aja yg cocok.
            $layer = StokFifoLayer::where('barang_id', $k->barang_id)
                ->where('harga_unit', $hppKembali)
                ->whereRaw('qty_sisa < qty_masuk')
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($layer) {
                // Jangan kembalikan lebih dari qty_masuk
                $space = $layer->qty_masuk - $layer->qty_sisa;
                $restore = min($space, $qtyKembali);
                $layer->increment('qty_sisa', $restore);
                $qtyKembali -= $restore;
                
                // Kalau masih sisa, lempar ke layer sebelumnya
                if ($qtyKembali > 0) {
                    $layer2 = StokFifoLayer::where('barang_id', $k->barang_id)
                        ->whereRaw('qty_sisa < qty_masuk')
                        ->orderBy('tanggal', 'desc')->first();
                    if ($layer2) {
                         $layer2->increment('qty_sisa', $qtyKembali);
                    }
                }
            }

            $barang->increment('stok', (int) $k->keluar);
            $k->delete();
        }
    }

    /**
     * Menghasilkan rekap kartu stok per barang untuk bulan/tahun yang dipilih.
     * Digunakan bersama oleh KartuStokPage dan KartuStokPdfController.
     */
    public function getLaporanData(string $bulan, string $tahun): array
    {
        $tglMulai = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $tglAkhir = $tglMulai->copy()->endOfMonth();

        $semuaBarang = Barang::orderBy('nama_barang')->get();
        $laporan     = [];

        foreach ($semuaBarang as $barang) {
            $masuks  = $this->getEventMasuk($barang->id, $tglAkhir);
            $keluars = $this->getEventKeluar($barang->id, $tglAkhir);

            $allEvents = $masuks
                ->concat($keluars)
                ->sortBy('tanggal')
                ->values();

            [$saldoAwalQty, $saldoAwalNilai, $masukQty, $masukNilai, $keluarQty, $keluarNilai, $fifoState] =
                $this->hitungFifo($allEvents, $tglMulai);

            [$saldoAkhirQty, $saldoAkhirNilai] = $this->hitungSaldoAkhir($fifoState);

            if ($saldoAwalQty === 0 && $masukQty === 0 && $keluarQty === 0 && $saldoAkhirQty === 0) {
                continue;
            }

            $laporan[] = [
                'barang'            => $barang,
                'saldo_awal_qty'    => $saldoAwalQty,
                'saldo_awal_nilai'  => $saldoAwalNilai,
                'masuk_qty'         => $masukQty,
                'masuk_nilai'       => $masukNilai,
                'keluar_qty'        => $keluarQty,
                'keluar_nilai'      => $keluarNilai,
                'saldo_akhir_qty'   => $saldoAkhirQty,
                'saldo_akhir_nilai' => $saldoAkhirNilai,
            ];
        }

        return $laporan;
    }

    private function getEventMasuk(int $barangId, Carbon $tglAkhir): Collection
    {
        return KartuStok::where('barang_id', $barangId)
            ->where('masuk', '>', 0)
            ->where('tanggal', '<=', $tglAkhir->format('Y-m-d'))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(fn($k) => [
                'tanggal' => $k->tanggal ? Carbon::parse($k->tanggal)->format('Y-m-d') : '2000-01-01',
                'type'    => 'masuk',
                'qty'     => (int) $k->masuk,
                'harga'   => (float) ($k->harga ?? 0),
            ]);
    }

    private function getEventKeluar(int $barangId, Carbon $tglAkhir): Collection
    {
        return PenjualanDetail::join('penjualan', 'penjualan.id', '=', 'penjualan_detail.penjualan_id')
            ->where('penjualan_detail.barang_id', $barangId)
            ->where('penjualan.tanggal_faktur', '<=', $tglAkhir->format('Y-m-d'))
            ->orderBy('penjualan.tanggal_faktur')
            ->orderBy('penjualan_detail.id')
            ->select('penjualan.tanggal_faktur as tanggal', 'penjualan_detail.qty')
            ->get()
            ->map(fn($r) => [
                'tanggal' => $r->tanggal,
                'type'    => 'keluar',
                'qty'     => (int) $r->qty,
                'harga'   => 0,
            ]);
    }

    private function hitungFifo(Collection $allEvents, Carbon $tglMulai): array
    {
        $fifoState      = [];
        $saldoAwalQty   = 0;
        $saldoAwalNilai = 0;
        $masukQty       = 0;
        $masukNilai     = 0;
        $keluarQty      = 0;
        $keluarNilai    = 0;
        $awalBulanFlag  = true;

        foreach ($allEvents as $event) {
            $isBulanIni = Carbon::parse($event['tanggal'])->format('Y-m') === $tglMulai->format('Y-m');

            if ($isBulanIni && $awalBulanFlag) {
                foreach ($fifoState as $layer) {
                    $saldoAwalQty   += $layer['qty'];
                    $saldoAwalNilai += $layer['qty'] * $layer['harga'];
                }
                $awalBulanFlag = false;
            }

            if ($event['type'] === 'masuk') {
                if ($event['qty'] > 0) {
                    $fifoState[] = ['qty' => $event['qty'], 'harga' => $event['harga']];
                }
                if ($isBulanIni) {
                    $masukQty   += $event['qty'];
                    $masukNilai += $event['qty'] * $event['harga'];
                }
            } else {
                $sisaKeluar = $event['qty'];
                $hppBatch   = 0;
                foreach ($fifoState as &$layer) {
                    if ($sisaKeluar <= 0) break;
                    if ($layer['qty'] <= 0) continue;
                    $ambil = min($sisaKeluar, $layer['qty']);
                    if ($isBulanIni) {
                        $hppBatch += $ambil * $layer['harga'];
                    }
                    $layer['qty'] -= $ambil;
                    $sisaKeluar   -= $ambil;
                }
                unset($layer);
                $fifoState = array_values(array_filter($fifoState, fn($l) => $l['qty'] > 0));
                if ($isBulanIni) {
                    $keluarQty   += $event['qty'];
                    $keluarNilai += $hppBatch;
                }
            }
        }

        if ($awalBulanFlag) {
            foreach ($fifoState as $layer) {
                $saldoAwalQty   += $layer['qty'];
                $saldoAwalNilai += $layer['qty'] * $layer['harga'];
            }
        }

        return [$saldoAwalQty, $saldoAwalNilai, $masukQty, $masukNilai, $keluarQty, $keluarNilai, $fifoState];
    }

    private function hitungSaldoAkhir(array $fifoState): array
    {
        $qty   = 0;
        $nilai = 0;
        foreach ($fifoState as $layer) {
            $qty   += $layer['qty'];
            $nilai += $layer['qty'] * $layer['harga'];
        }
        return [$qty, $nilai];
    }
}
