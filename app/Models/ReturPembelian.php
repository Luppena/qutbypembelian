<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ReturPembelian extends Model
{
    protected $table = 'retur_pembelians';

    protected $fillable = [
        'nomor_retur',
        'grn_id',
        'pembelian_id',
        'vendor_id',
        'tanggal_retur',
        'alasan_utama',
        'foto_bukti',
        'keterangan',
        'penyelesaian',
        'status',
        'dibuat_oleh',
        'disetujui_oleh',
        'disetujui_at',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
        'disetujui_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->nomor_retur)) {
                $model->nomor_retur = self::generateNomor();
            }
            if (empty($model->dibuat_oleh)) {
                $model->dibuat_oleh = auth()->id();
            }
        });
    }

    public static function generateNomor(): string
    {
        $tahun = now()->year;
        $prefix = "RTR-{$tahun}-";

        $last = DB::table('retur_pembelians')
            ->where('nomor_retur', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('nomor_retur');

        $lastNumber = $last ? (int) substr($last, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /* ==========================
     | RELATIONS
     ========================== */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReturPembelianDetail::class, 'retur_pembelian_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'dibuat_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'disetujui_oleh');
    }

    /* ==========================
     | HELPERS
     ========================== */
    public function getTotalRetur(): float
    {
        return $this->details->sum('subtotal');
    }

    public function getAlasanLabel(): string
    {
        return match($this->alasan_utama) {
            'rusak'          => 'Barang Rusak/Cacat',
            'tidak_sesuai'   => 'Tidak Sesuai Pesanan',
            'kelebihan_qty'  => 'Kelebihan Qty',
            'kualitas_tidak_sesuai' => 'Kualitas Tidak Sesuai',
            'salah_kirim'    => 'Barang Salah Kirim',
            default          => $this->alasan_utama,
        };
    }
}
