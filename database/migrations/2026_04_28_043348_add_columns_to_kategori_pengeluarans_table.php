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
        Schema::table('kategori_pengeluarans', function (Blueprint $table) {
            $table->string('nama')->after('id')->comment('Nama kategori pengeluaran');
            $table->unsignedBigInteger('daftar_akun_id')->nullable()->after('nama')->comment('Akun beban yang terkait');
            $table->foreign('daftar_akun_id')->references('id')->on('daftar_akuns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategori_pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['daftar_akun_id']);
            $table->dropColumn(['nama', 'daftar_akun_id']);
        });
    }
};
