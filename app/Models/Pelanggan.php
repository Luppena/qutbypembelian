<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';
    protected $guarded = [];

    public static function generateNextKodePelanggan(): string
    {
        $last = static::query()
            ->where('kode_pelanggan', 'like', 'PLG-%')
            ->orderByDesc('id')
            ->value('kode_pelanggan');

        $lastNumber = $last ? (int) substr($last, 4) : 0;
        $nextNumber = $lastNumber + 1;

        return 'PLG-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
