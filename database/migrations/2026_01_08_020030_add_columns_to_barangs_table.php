<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::table('barangs', function (Blueprint $table) {
        // Hanya tambahkan jika kolom BELUM ada
        if (!Schema::hasColumn('barangs', 'kode_barang')) {
            $table->string('kode_barang')->unique()->after('id');
        }
        
        // Hapus baris nama_barang dari sini jika sudah ada di database
        // atau gunakan pengecekan seperti ini:
        if (!Schema::hasColumn('barangs', 'nama_barang')) {
            $table->string('nama_barang')->after('kode_barang');
        }

        if (!Schema::hasColumn('barangs', 'satuan')) {
            $table->string('satuan')->default('Pcs');
        }

        if (!Schema::hasColumn('barangs', 'stok')) {
            $table->integer('stok')->default(0);
        }

        if (!Schema::hasColumn('barangs', 'hpp_satuan')) {
            $table->decimal('hpp_satuan', 15, 2)->default(0);
        }

        if (!Schema::hasColumn('barangs', 'harga_barang')) {
            $table->decimal('harga_barang', 15, 2)->default(0);
        }

        if (!Schema::hasColumn('barangs', 'foto')) {
            $table->string('foto')->nullable();
        }
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            //
        });
    }
};
