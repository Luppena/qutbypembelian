<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kategori_pengeluarans', function (Blueprint $table) {
            $table->foreignId('daftar_akun_id')
                ->nullable()
                ->constrained('daftar_akuns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kategori_pengeluarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daftar_akun_id');
        });
    }
};
