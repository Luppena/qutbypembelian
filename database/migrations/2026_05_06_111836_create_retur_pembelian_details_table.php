<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelian_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('retur_pembelians')->cascadeOnDelete();
            $table->foreignId('grn_detail_id')->constrained('grn_details')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty_retur');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('kondisi')->nullable();    // kondisi barang yang diretur
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_pembelian_details');
    }
};
