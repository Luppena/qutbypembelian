<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grn_details', function (Blueprint $table) {
            $table->unsignedInteger('qty_rusak')->default(0)->after('qty_diterima');
        });

        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('menunggu','menunggu_pickup','disetujui','selesai','selesai_retur','dibatalkan') DEFAULT 'menunggu_pickup'");
    }

    public function down(): void
    {
        Schema::table('grn_details', function (Blueprint $table) {
            $table->dropColumn('qty_rusak');
        });

        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('menunggu','disetujui','selesai','dibatalkan') DEFAULT 'menunggu'");
    }
};
