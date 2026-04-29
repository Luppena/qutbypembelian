<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tabel belum ada sebelum membuat
        if (!Schema::hasTable('pembelian_details')) {
            Schema::create('pembelian_details', function (Blueprint $table) {
    $table->id();

    $table->foreignId('pembelian_id')
        ->constrained('pembelians')
        ->cascadeOnDelete();

    $table->foreignId('barang_id')
        ->constrained('barangs')
        ->restrictOnDelete();

    $table->integer('qty')->default(1);
    $table->string('satuan', 20);
    $table->decimal('harga', 15, 2);
    $table->decimal('subtotal', 15, 2);

    $table->timestamps();
});

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_details'); // Menghapus tabel jika rollback
    }
};
