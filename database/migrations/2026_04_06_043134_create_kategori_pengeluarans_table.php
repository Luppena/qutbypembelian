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
        Schema::create('kategori_pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->comment('Nama kategori pengeluaran');
            $table->unsignedBigInteger('daftar_akun_id')->nullable()->comment('Akun beban yang terkait');
            $table->foreign('daftar_akun_id')->references('id')->on('daftar_akuns')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_pengeluarans');
    }
};
