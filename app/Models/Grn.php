<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Grn extends Model
{
    protected $table = 'grns';

    protected $fillable = [
        'nomor_grn',
        'pembelian_id',
        'vendor_id',
        'tanggal_terima',
        'catatan',
        'status',
        'dikonfirmasi_oleh',
        'dikonfirmasi_at',
    ];

    protected $casts = [
        'tanggal_terima'  => 'date',
        'dikonfirmasi_at' => 'datetime',
    ];

    /* ==========================
     | BOOT
     ========================== */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->nomor_grn)) {
                $model->nomor_grn = self::generateNomor();
            }

            while (self::where('nomor_grn', $model->nomor_grn)->exists()) {
                $lastNumber = (int) substr($model->nomor_grn, -4);
                $model->nomor_grn = substr($model->nomor_grn, 0, -4) . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public static function generateNomor(): string
    {
        $tahun = now()->year;
        $prefix = "GRN-{$tahun}-";

        $last = DB::table('grns')
            ->where('nomor_grn', 'like', "{$prefix}%")
            ->orderByDesc('nomor_grn')
            ->value('nomor_grn');

        $lastNumber = $last ? (int) substr($last, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /* ==========================
     | RELATIONS
     ========================== */
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
        return $this->hasMany(GrnDetail::class, 'grn_id');
    }

    public function returPembelians(): HasMany
    {
        return $this->hasMany(ReturPembelian::class, 'grn_id');
    }

    /* ==========================
     | HELPERS
     ========================== */
    public function hasItemRusak(): bool
    {
        return $this->details()
            ->whereIn('kondisi', ['rusak_sebagian', 'rusak_semua'])
            ->exists();
    }

    public function hasSelisihQty(): bool
    {
        $this->loadMissing(['details.pembelianDetail.grnDetails.grn']);

        return $this->details->contains(function (GrnDetail $detail) {
            $poDetail = $detail->pembelianDetail;

            if (! $poDetail) {
                return false;
            }

            $qtySebelumnya = $poDetail->grnDetails
                ->filter(fn (GrnDetail $grnDetail) => $grnDetail->grn?->status === 'dikonfirmasi')
                ->sum('qty_diterima');

            return ($qtySebelumnya + (int) $detail->qty_diterima) !== (int) $poDetail->qty;
        });
    }

    public function hasOverQuantity(): bool
    {
        $this->loadMissing(['details.pembelianDetail.grnDetails.grn']);

        return $this->details->contains(function (GrnDetail $detail) {
            $poDetail = $detail->pembelianDetail;

            if (! $poDetail) {
                return false;
            }

            $qtySebelumnya = $poDetail->grnDetails
                ->filter(fn (GrnDetail $grnDetail) => $grnDetail->grn?->status === 'dikonfirmasi')
                ->sum('qty_diterima');

            return ($qtySebelumnya + (int) $detail->qty_diterima) > (int) $poDetail->qty;
        });
    }
}
