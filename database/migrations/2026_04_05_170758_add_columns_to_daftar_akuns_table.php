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
        Schema::table('daftar_akuns', function (Blueprint $table) {
            $table->boolean('header_akun')->default(false);
            $table->string('kode_akun')->nullable();
            $table->string('nama_akun')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('saldo_normal')->default('debit');
            $table->decimal('saldo_awal_nominal', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daftar_akuns', function (Blueprint $table) {
            $table->dropColumn([
                'header_akun',
                'kode_akun',
                'nama_akun',
                'parent_id',
                'saldo_normal',
                'saldo_awal_nominal'
            ]);
        });
    }
};
