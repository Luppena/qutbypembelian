<?php

namespace App\Observers;

use App\Models\PembayaranPembelian;
use App\Models\KartuUtang;

class PembayaranPembelianObserver
{
    public function created(PembayaranPembelian $pembayaran): void
    {
        $noBukti = 'PAY-' . $pembayaran->id;

        // ✅ anti dobel posting
        if (KartuUtang::where('no_bukti', $noBukti)->exists()) {
            return;
        }

        $saldoSebelumnya = (float) (KartuUtang::where('vendor_id', $pembayaran->vendor_id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->value('saldo') ?? 0);

        $kredit = (float) ($pembayaran->nilai_pembayaran ?? 0);
        $saldoBaru = $saldoSebelumnya - $kredit;

        KartuUtang::create([
            'vendor_id'  => $pembayaran->vendor_id,
            'tanggal'    => $pembayaran->tanggal_pembayaran ?? now()->toDateString(),
            'no_bukti'   => $noBukti,
            'keterangan' => 'Pembayaran Pembelian',
            'debet'      => 0,
            'kredit'     => $kredit,
            'saldo'      => $saldoBaru,
        ]);
    }
}
