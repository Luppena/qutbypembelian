<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $guarded = [];

    protected $casts = [
        'tanggal_faktur' => 'date',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }



public function detail(): HasMany
{
    return $this->hasMany(PenjualanDetail::class, 'penjualan_id');
}



    public function hitungTotalHpp(): int
    {
        return $this->detail->sum(function ($detail) {
            return $detail->qty * ($detail->barang->hpp_satuan ?? 0);
        });
    }
    public static function generateNextNoFaktur(): string
    {
        $last = static::query()
            ->where('no_faktur', 'like', 'FKT-%')
            ->orderByDesc('id')
            ->value('no_faktur');

        $lastNumber = $last ? (int) substr($last, 4) : 0;
        $nextNumber = $lastNumber + 1;

        return 'FKT-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function vendor()
{
    return $this->belongsTo(Vendor::class);
}

}