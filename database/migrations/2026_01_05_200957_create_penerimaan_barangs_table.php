<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('id_penerimaan')->unique();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('keterangan')->nullable();
            $table->enum('status', ['draft', 'posted', 'batal'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barangs');
    }
};
