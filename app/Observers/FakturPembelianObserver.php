<?php

namespace App\Observers;

use App\Models\FakturPembelian;
use App\Models\KartuUtang;

class FakturPembelianObserver
{
    public function created(FakturPembelian $faktur): void
    {
        // ❗ cegah dobel posting
        if (KartuUtang::where('no_bukti', $faktur->nomor_faktur_vendor)->exists()) {
            return;
        }

        $saldoSebelumnya = KartuUtang::where('vendor_id', $faktur->vendor_id)
            ->orderByDesc('id')
            ->value('saldo') ?? 0;

        $debet = (float) $faktur->total_netto;
        $saldoBaru = $saldoSebelumnya + $debet;

        KartuUtang::create([
            'vendor_id'  => $faktur->vendor_id,
            'tanggal'    => $faktur->tanggal_faktur,
            'no_bukti'   => $faktur->nomor_faktur_vendor,
            'keterangan' => 'Faktur Pembelian ' . $faktur->nomor_faktur_vendor,
            'debet'      => $debet,
            'kredit'     => 0,
            'saldo'      => $saldoBaru,
        ]);
    }
}
