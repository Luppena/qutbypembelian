<?php

namespace App\Observers;

use App\Models\Pembelian;

class PembelianObserver
{
    public function creating(Pembelian $pembelian): void
    {
        // Tidak ada proses stok di pesanan pembelian.
    }

    public function saved(Pembelian $pembelian): void
    {
        // Hanya sinkronisasi stok dan fitur FIFO jika statusnya adalah diterima secara fisik / lunas.
        if ($pembelian->status === 'diterima' || $pembelian->status === 'lunas') {
            $items = $pembelian->details ?? collect();
            app(\App\Services\KartuStokService::class)->syncPembelian($pembelian, $items);
        } else {
            // Jika ditarik kembali/batal, hapus layer stok
            app(\App\Services\KartuStokService::class)->rollbackPembelian($pembelian->id);
        }
    }

    public function deleted(Pembelian $pembelian): void
    {
        app(\App\Services\KartuStokService::class)->rollbackPembelian($pembelian->id);
    }
}
