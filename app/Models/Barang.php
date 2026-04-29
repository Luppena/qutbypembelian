<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barangs';

    protected $guarded = [];

    public static function getKodeBarang()
    {
        $sql = "SELECT IFNULL(MAX(kode_barang), 'BRG000') as kode_barang 
                FROM barangs"; // ✅ sudah plural

        $kodebarang = DB::select($sql);

        $kd = $kodebarang[0]->kode_barang ?? 'BRG000';

        $noawal  = substr($kd, -3);
        $noakhir = (int) $noawal + 1;

        return 'BRG' . str_pad($noakhir, 3, "0", STR_PAD_LEFT);
    }

    public function setHargaBarangAttribute($value)
    {
        $this->attributes['harga_barang'] = str_replace('.', '', $value);
    }

    public function penjualanBarang()
    {
        return $this->hasMany(PenjualanBarang::class, 'barang_id');
    }

    public function stokBarang()
    {
        return $this->hasMany(StokBarang::class, 'barang_id');
    }
     public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
}
