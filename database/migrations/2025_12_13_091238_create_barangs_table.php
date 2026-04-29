<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id(); // PRIMARY KEY

            $table->string('nama_barang');
            $table->integer('harga_barang');
            $table->string('satuan', 50)->nullable();
            $table->integer('stok')->default(0);
            $table->string('foto')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
