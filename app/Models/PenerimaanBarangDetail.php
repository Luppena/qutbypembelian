<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaanBarangDetail extends Model
{
    use HasFactory;

    protected $table = 'penerimaan_barang_details';

    protected $fillable = [
        'penerimaan_barang_id',
        'barang_id',
        'jumlah_diterima',
        'satuan',
    ];

    /**
     * Relasi ke header PenerimaanBarang
     */
    public function penerimaanBarang(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'penerimaan_barang_id');
    }

    /**
     * Relasi ke master Barang
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
