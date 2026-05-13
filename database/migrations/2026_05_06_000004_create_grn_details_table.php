<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('grns')->cascadeOnDelete();
            $table->foreignId('pembelian_detail_id')->constrained('pembelian_details')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->unsignedInteger('qty_po');
            $table->unsignedInteger('qty_diterima')->default(0);
            $table->enum('kondisi', ['baik', 'rusak_sebagian', 'rusak_semua'])->default('baik');
            $table->string('foto')->nullable();
            $table->text('catatan_item')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_details');
    }
};
