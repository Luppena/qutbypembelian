<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelians', function (Blueprint $table) {
    $table->id();
    $table->string('nomor')->unique();
    $table->date('tanggal');

    $table->foreignId('vendor_id')->constrained()->restrictOnDelete();

    $table->decimal('total', 15, 2)->default(0);
    $table->decimal('diskon', 5, 2)->default(0);
    $table->boolean('ppn')->default(true);
    $table->decimal('total_akhir', 15, 2)->default(0);

    $table->string('status')->default('proses');
    $table->text('keterangan')->nullable();

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};