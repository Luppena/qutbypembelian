<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_stok_average', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('keterangan');
            $table->enum('jenis', ['awal', 'beli', 'jual']);
            $table->integer('qty');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('hpp_per_unit', 15, 2)->default(0);
            $table->decimal('hpp_total', 15, 2)->default(0);
            $table->integer('sisa_unit');
            $table->decimal('harga_rata_rata', 15, 2);
            $table->decimal('nilai_persediaan', 15, 2);
            $table->timestamps();

            $table->index(['barang_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_stok_average');
    }
};
