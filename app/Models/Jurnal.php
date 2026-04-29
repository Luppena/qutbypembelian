<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurnal extends Model
{
    protected $fillable = [
        'tanggal',
        'referensi',
        'keterangan',
    ];

    /**
     * Get the details for the journal entry.
     */
    public function details(): HasMany
    {
        return $this->hasMany(JurnalDetail::class);
    }
}
