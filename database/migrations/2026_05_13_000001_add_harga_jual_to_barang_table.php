<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            if (! Schema::hasColumn('barang', 'harga_jual')) {
                $table->decimal('harga_jual', 15, 2)->default(0)->after('harga_beli');
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            if (Schema::hasColumn('barang', 'harga_jual')) {
                $table->dropColumn('harga_jual');
            }
        });
    }
};
