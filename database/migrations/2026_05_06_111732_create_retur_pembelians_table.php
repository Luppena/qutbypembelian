<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur')->unique();
            $table->foreignId('grn_id')->constrained('grns')->cascadeOnDelete();
            $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('tanggal_retur');
            $table->string('alasan_utama');           // rusak, tidak_sesuai, kelebihan_qty, lainnya
            $table->text('keterangan')->nullable();
            $table->enum('status', ['draft', 'disetujui', 'selesai', 'dibatalkan'])->default('draft');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_pembelians');
    }
};
