<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PenerimaanBarang extends Model
{
    protected $table = 'penerimaan_barangs';

    protected $fillable = [
        'id_penerimaan',
        'vendor_id',
        'pembelian_id',
        'tanggal',
        'keterangan',
    ];

    /* ======================
     | BOOT
     ====================== */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id_penerimaan)) {
                $model->id_penerimaan = self::generateIdPenerimaan();
            }
        });
    }

    public static function generateIdPenerimaan(): string
    {
        $tahun = now()->year;

        $last = DB::table('penerimaan_barangs')
            ->whereYear('created_at', $tahun)
            ->orderByDesc('id')
            ->value('id_penerimaan');

        $lastNumber = $last ? (int) substr($last, -4) : 0;

        return 'PN-' . $tahun . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /* ======================
     | RELATIONS
     ====================== */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PenerimaanBarangDetail::class, 'penerimaan_barang_id');
    }
}
