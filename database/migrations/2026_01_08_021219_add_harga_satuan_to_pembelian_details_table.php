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
    Schema::table('pembelian_details', function (Blueprint $table) {
        if (!Schema::hasColumn('pembelian_details', 'harga_satuan')) {
            // Menggunakan bigInteger atau decimal untuk harga agar akurat
            $table->decimal('harga_satuan', 15, 2)->default(0)->after('barang_id');
        }
    });
}

public function down(): void
{
    Schema::table('pembelian_details', function (Blueprint $table) {
        $table->dropColumn('harga_satuan');
    });
}
};
