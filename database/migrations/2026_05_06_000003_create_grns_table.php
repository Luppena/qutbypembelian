<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_grn')->unique();
            $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('tanggal_terima');
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'dikonfirmasi'])->default('draft');
            $table->unsignedBigInteger('dikonfirmasi_oleh')->nullable();
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
