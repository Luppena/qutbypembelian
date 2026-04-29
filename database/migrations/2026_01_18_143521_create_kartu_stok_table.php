<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('keterangan')->nullable();
            $table->integer('masuk')->default(0);
            $table->integer('keluar')->default(0);
            $table->integer('saldo')->default(0);
            $table->decimal('harga', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_stok');
    }
};
