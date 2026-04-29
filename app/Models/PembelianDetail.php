<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianDetail extends Model
{
    protected $table = 'pembelian_details';

    protected $fillable = [
        'pembelian_id',
        'barang_id',
        'qty',
        'satuan',
        'harga',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga' => 'float',
        'subtotal' => 'float',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
