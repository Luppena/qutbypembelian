<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriPengeluaran extends Model
{
    protected $fillable = [
        'nama',
        'daftar_akun_id',
    ];

    /**
     * Relasi ke akun beban
     */
    public function daftarAkun(): BelongsTo
    {
        return $this->belongsTo(DaftarAkun::class, 'daftar_akun_id');
    }
}
