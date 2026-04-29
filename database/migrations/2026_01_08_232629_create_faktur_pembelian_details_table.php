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
        Schema::create('faktur_pembelian_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('faktur_pembelian_id')
                ->constrained('faktur_pembelians')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('qty');
            $table->decimal('harga', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faktur_pembelian_details');
    }
};
