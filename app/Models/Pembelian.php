<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Pembelian extends Model
{
    protected $table = 'pembelians';

    protected $fillable = [
        'tanggal',
        'nomor',
        'vendor_id',
        'total',
        'diskon',
        'ppn',
        'total_akhir',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'ppn' => 'boolean',
        'total' => 'float',
        'diskon' => 'float',
        'total_akhir' => 'float',
    ];

    /**
     * Generate nomor pembelian otomatis
     * Format: PB-YYYY-0001
     */
    public static function generateNomorPembelian(): string
    {
        $tahun = Carbon::now()->format('Y');

        $lastNomor = self::query()
            ->where('nomor', 'like', "PB-$tahun-%")
            ->orderByDesc('id')
            ->value('nomor');

        $nextNumber = 1;

        if ($lastNomor) {
            $lastSeq = (int) substr($lastNomor, -4);
            $nextNumber = $lastSeq + 1;
        }

        return "PB-$tahun-" . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Detail item pembelian
     */
    public function details(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id');
    }

    /**
     * Relasi ke Penerimaan Barang (1 Pembelian = 1 Penerimaan Barang)
     */
    public function penerimaanBarang(): HasOne
    {
        return $this->hasOne(PenerimaanBarang::class, 'pembelian_id');
    }

    /**
     * Relasi ke Faktur Pembelian (1 Pembelian = 1 Faktur)
     * Pastikan tabel faktur punya kolom pembelian_id
     */
    public function fakturPembelian(): HasOne
    {
        return $this->hasOne(FakturPembelian::class, 'pembelian_id');
    }

    protected static function booted()
    {
        static::created(function (self $pembelian) {
            $tanggal = $pembelian->tanggal ?? now()->toDateString();
            $ref     = $pembelian->nomor ?? ('PB-' . $pembelian->id);

            // ✅ Anti-duplikasi: skip jika jurnal sudah ada  
            if (\App\Models\Jurnal::where('referensi', $ref)->exists()) {
                return;
            }

            // Load relasi vendor jika belum di-load
            $pembelian->loadMissing('vendor');

            // Dapatkan/buat akun Persediaan (Metode Perpetual)
            $akunPersediaan = \App\Models\DaftarAkun::firstOrCreate(
                ['kode_akun' => '114'],
                ['nama_akun' => 'Persediaan Barang Dagang', 'saldo_normal' => 'debit']
            );

            // Dapatkan/buat akun Utang
            $akunUtang = \App\Models\DaftarAkun::firstOrCreate(
                ['kode_akun' => '211'],
                ['nama_akun' => 'Utang Usaha', 'saldo_normal' => 'kredit']
            );

            $akunPpnMasukan = null;
            if ($pembelian->ppn) {
                $akunPpnMasukan = \App\Models\DaftarAkun::firstOrCreate(
                    ['kode_akun' => '115'],
                    ['nama_akun' => 'PPN Masukan', 'saldo_normal' => 'debit']
                );
            }

            // Buat header Jurnal
            $jurnal = \App\Models\Jurnal::create([
                'tanggal'    => $tanggal,
                'referensi'  => $ref,
                'keterangan' => 'Pembelian barang dagang secara kredit',
            ]);

            // Hitung nilai DPP & PPN dari master Pembelian
            $total = (float) ($pembelian->total ?? 0);
            $diskonNominal = $total * ((float) ($pembelian->diskon ?? 0) / 100);
            $dpp = max($total - $diskonNominal, 0);
            
            $ppnNominal = $pembelian->ppn ? $dpp * 0.11 : 0;
            $totalAkhir = (float) ($pembelian->total_akhir ?? 0);
            if ($totalAkhir <= 0) {
                $totalAkhir = $dpp + $ppnNominal;
            }

            // [D] Persediaan
            $jurnal->details()->create([
                'daftar_akun_id' => $akunPersediaan->id,
                'keterangan'     => 'Persediaan masuk ' . $pembelian->nomor,
                'debit'          => $dpp,
                'kredit'         => 0,
            ]);

            // [D] PPN Masukan
            if ($pembelian->ppn && $ppnNominal > 0 && $akunPpnMasukan) {
                $jurnal->details()->create([
                    'daftar_akun_id' => $akunPpnMasukan->id,
                    'keterangan'     => 'Pajak masukan',
                    'debit'          => $ppnNominal,
                    'kredit'         => 0,
                ]);
            }

            // [K] Utang Usaha
            $jurnal->details()->create([
                'daftar_akun_id' => $akunUtang->id,
                'keterangan'     => 'Kewajiban utang Pemasok',
                'debit'          => 0,
                'kredit'         => $totalAkhir,
            ]);
        });
    }
}
