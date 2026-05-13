<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturPembelianDetail extends Model
{
    protected $table = 'retur_pembelian_details';

    protected $fillable = [
        'retur_pembelian_id',
        'grn_detail_id',
        'barang_id',
        'qty_retur',
        'harga_satuan',
        'subtotal',
        'kondisi',
        'catatan',
    ];

    protected $casts = [
        'qty_retur'    => 'integer',
        'harga_satuan' => 'float',
        'subtotal'     => 'float',
    ];

    public function returPembelian(): BelongsTo
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_pembelian_id');
    }

    public function grnDetail(): BelongsTo
    {
        return $this->belongsTo(GrnDetail::class, 'grn_detail_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
