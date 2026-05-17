<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->string('nomor_surat_jalan')->nullable()->after('tanggal_terima');
            $table->string('gudang_tujuan')->default('gudang_utama')->after('nomor_surat_jalan');
        });

        DB::statement("ALTER TABLE grn_details MODIFY kondisi VARCHAR(30) NOT NULL DEFAULT 'baik'");
    }

    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->dropColumn(['nomor_surat_jalan', 'gudang_tujuan']);
        });

        DB::statement("ALTER TABLE grn_details MODIFY kondisi ENUM('baik', 'rusak_sebagian', 'rusak_semua') NOT NULL DEFAULT 'baik'");
    }
};
