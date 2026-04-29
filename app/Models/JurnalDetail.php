<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalDetail extends Model
{
    protected $fillable = [
        'jurnal_id',
        'daftar_akun_id',
        'debit',
        'kredit',
        'keterangan',
    ];

    /**
     * Get the journal that owns the detail.
     */
    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class);
    }

    /**
     * Get the account associated with the detail.
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(DaftarAkun::class, 'daftar_akun_id');
    }
}
