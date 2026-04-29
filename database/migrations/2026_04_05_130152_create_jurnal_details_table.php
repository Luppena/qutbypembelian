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
        Schema::create('jurnal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_id')->constrained('jurnals')->cascadeOnDelete();
            $table->foreignId('daftar_akun_id')->constrained('daftar_akuns')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_details');
    }
};
