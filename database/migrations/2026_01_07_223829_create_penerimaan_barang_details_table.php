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
        Schema::create('penerimaan_barang_details', function (Blueprint $table) {
    $table->id();

    $table->foreignId('penerimaan_barang_id')
        ->constrained('penerimaan_barangs')
        ->cascadeOnDelete();

    $table->foreignId('barang_id')
        ->constrained('barangs');

    $table->integer('jumlah_diterima');
    $table->string('satuan', 50);
    $table->enum('kondisi_barang', ['baik', 'rusak', 'cacat']);
    $table->string('keterangan')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barang_details');
    }
};
