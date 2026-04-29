<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokFifoLayer extends Model
{
    protected $table = 'stok_fifo_layers';

    protected $fillable = [
        'barang_id',
        'tanggal',
        'source_type',
        'source_id',
        'source_line_id',
        'qty_masuk',
        'qty_sisa',
        'harga_unit',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
