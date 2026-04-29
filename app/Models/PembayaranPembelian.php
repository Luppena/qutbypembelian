<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPembelian extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'tanggal_pembayaran',
        'faktur_pembelian_id',
        'vendor_id',
        'bank',
        'no_rekening',
        'nilai_pembayaran',
    ];

    protected static function booted()
    {
        static::created(function (self $pembayaran) {
            $tanggal = $pembayaran->tanggal_pembayaran ?? now()->toDateString();
            $ref     = 'PAY-PB-' . $pembayaran->id;

            // ✅ Anti-duplikasi: skip jika jurnal sudah ada
            if (\App\Models\Jurnal::where('referensi', $ref)->exists()) {
                return;
            }

            // Load relasi vendor jika belum
            $pembayaran->loadMissing('vendor');

            // 1. Dapatkan atau buat akun Utang Usaha (Liabilitas)
            $akunUtang = \App\Models\DaftarAkun::firstOrCreate(
                ['kode_akun' => '211'], // Atau sesuaikan kode akun utang Anda
                ['nama_akun' => 'Utang Usaha', 'saldo_normal' => 'kredit']
            );

            // 2. Dapatkan atau buat akun Kas/Bank (Aset) berdasarkan nama bank
            $namaBank = $pembayaran->bank ? ucwords(strtolower($pembayaran->bank)) : 'Kas';
            $labelBank = $pembayaran->bank ? 'Bank ' . $namaBank : 'Kas';
            $akunKas = \App\Models\DaftarAkun::firstOrCreate(
                ['nama_akun' => $labelBank],
                ['kode_akun' => 'KAS-' . strtoupper(substr($namaBank, 0, 3)), 'saldo_normal' => 'debit']
            );

            // 3. Buat Header Jurnal
            $jurnal = \App\Models\Jurnal::create([
                'tanggal'    => $tanggal,
                'referensi'  => $ref,
                'keterangan' => 'Pembayaran ke Vendor ' . ($pembayaran->vendor->nama_vendor ?? '-') . ' via ' . $labelBank,
            ]);

            $nominal = (float) ($pembayaran->nilai_pembayaran ?? 0);

            // 4. Detail Jurnal (Debit: Utang Usaha)
            $jurnal->details()->create([
                'daftar_akun_id' => $akunUtang->id,
                'keterangan'     => 'Pelunasan faktur pembelian',
                'debit'          => $nominal,
                'kredit'         => 0,
            ]);

            // 5. Detail Jurnal (Kredit: Kas/Bank)
            $jurnal->details()->create([
                'daftar_akun_id' => $akunKas->id,
                'keterangan'     => 'Keluar uang dari ' . $namaBank,
                'debit'          => 0,
                'kredit'         => $nominal,
            ]);
        });
    }

    public function fakturPembelian()
    {
        return $this->belongsTo(FakturPembelian::class, 'faktur_pembelian_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
