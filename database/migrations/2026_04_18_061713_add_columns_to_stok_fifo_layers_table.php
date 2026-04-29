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
        Schema::table('stok_fifo_layers', function (Blueprint $table) {
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('source_type')->nullable(); // misal 'pembelian'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            
            $table->integer('qty_masuk')->default(0);
            $table->integer('qty_sisa')->default(0);
            $table->decimal('harga_unit', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('stok_fifo_layers', function (Blueprint $table) {
            $table->dropForeign(['barang_id']);
            $table->dropColumn([
                'barang_id', 'tanggal', 'source_type', 'source_id',
                'source_line_id', 'qty_masuk', 'qty_sisa', 'harga_unit'
            ]);
        });
    }
};
