<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnDetail;
use App\Models\KartuStok;
use App\Models\StokFifoLayer;
use Illuminate\Support\Facades\DB;

class GrnKonfirmasiService
{
    /**
     * Konfirmasi GRN: update stok FIFO + kartu stok untuk setiap item
     * yang kondisinya baik atau rusak_sebagian.
     */
    public function konfirmasi(Grn $grn, int $userId): void
    {
        DB::transaction(function () use ($grn, $userId) {
            if ($grn->status !== 'draft') {
                throw new \RuntimeException('GRN ini sudah dikonfirmasi.');
            }

            $grn->load(['details.barang', 'details.pembelianDetail', 'pembelian']);

            $totalDppAcrual = 0;

            foreach ($grn->details as $detail) {
                /** @var GrnDetail $detail */
                if ($detail->kondisi === 'rusak_semua') {
                    continue;
                }

                $qtyMasuk = (int) $detail->qty_diterima;
                $hargaUnit = (int) ($detail->pembelianDetail->harga ?? 0);
                $barang = $detail->barang;

                if ($qtyMasuk <= 0 || ! $barang) {
                    continue;
                }

                $diskonPersen = (float) ($detail->pembelianDetail->diskon_persen ?? 0);
                $hargaDiskon = $hargaUnit * (1 - ($diskonPersen / 100));
                $totalDppAcrual += $qtyMasuk * $hargaDiskon;

                StokFifoLayer::create([
                    'barang_id' => $barang->id,
                    'tanggal' => $grn->tanggal_terima,
                    'source_type' => 'grn',
                    'source_id' => $grn->id,
                    'source_line_id' => $detail->id,
                    'qty_masuk' => $qtyMasuk,
                    'qty_sisa' => $qtyMasuk,
                    'harga_unit' => $hargaUnit,
                ]);

                $barang->increment('stok', $qtyMasuk);
                $stokAkhir = (int) ($barang->fresh()->stok ?? 0);

                KartuStok::create([
                    'barang_id' => $barang->id,
                    'tanggal' => $grn->tanggal_terima,
                    'is_saldo_awal' => false,
                    'keterangan' => 'Penerimaan GRN ' . $grn->nomor_grn,
                    'masuk' => $qtyMasuk,
                    'keluar' => 0,
                    'saldo' => $stokAkhir,
                    'harga' => $hargaUnit,
                    'hpp' => 0,
                    'source_type' => 'grn',
                    'source_id' => $grn->id,
                    'source_line_id' => $detail->id,
                ]);

                app(\App\Services\KartuStokService::class)->syncHargaBarang($barang);

                $barang->refresh();
                if ($barang->stok <= $barang->stok_minimum) {
                    \Illuminate\Support\Facades\Log::warning(
                        "Stok minimum: {$barang->nama_barang} " .
                        "(stok: {$barang->stok}, min: {$barang->stok_minimum})"
                    );
                }
            }

            $grn->update([
                'status' => 'dikonfirmasi',
                'dikonfirmasi_oleh' => $userId,
                'dikonfirmasi_at' => now(),
            ]);

            $grn->pembelian()->update(['status_pengiriman' => 'dalam_kirim']);
            $grn->pembelian?->refreshStatusPenerimaan();

            if ($totalDppAcrual > 0) {
                $pembelian = $grn->pembelian;
                $tanggal = $grn->tanggal_terima;
                $ref = $grn->nomor_grn;

                if (! \App\Models\Jurnal::where('referensi', $ref)->exists()) {
                    $akunPersediaan = \App\Models\DaftarAkun::firstOrCreate(
                        ['kode_akun' => '114'],
                        ['nama_akun' => 'Persediaan Barang Dagang', 'saldo_normal' => 'debit']
                    );

                    $akunUtang = \App\Models\DaftarAkun::firstOrCreate(
                        ['kode_akun' => '211'],
                        ['nama_akun' => 'Utang Usaha', 'saldo_normal' => 'kredit']
                    );

                    $jurnal = \App\Models\Jurnal::create([
                        'tanggal' => $tanggal,
                        'referensi' => $ref,
                        'keterangan' => 'Penerimaan GRN ' . $ref . ' (PO: ' . $pembelian->nomor . ')',
                    ]);

                    $ppnNominal = $pembelian->ppn ? $totalDppAcrual * 0.11 : 0;
                    $totalAkhir = $totalDppAcrual + $ppnNominal;

                    $jurnal->details()->create([
                        'daftar_akun_id' => $akunPersediaan->id,
                        'keterangan' => 'Persediaan masuk ' . $ref,
                        'debit' => $totalDppAcrual,
                        'kredit' => 0,
                    ]);

                    if ($pembelian->ppn && $ppnNominal > 0) {
                        $akunPpnMasukan = \App\Models\DaftarAkun::firstOrCreate(
                            ['kode_akun' => '115'],
                            ['nama_akun' => 'PPN Masukan', 'saldo_normal' => 'debit']
                        );

                        $jurnal->details()->create([
                            'daftar_akun_id' => $akunPpnMasukan->id,
                            'keterangan' => 'Pajak masukan',
                            'debit' => $ppnNominal,
                            'kredit' => 0,
                        ]);
                    }

                    $jurnal->details()->create([
                        'daftar_akun_id' => $akunUtang->id,
                        'keterangan' => 'Kewajiban utang Pemasok',
                        'debit' => 0,
                        'kredit' => $totalAkhir,
                    ]);
                }
            }
        });
    }
}
