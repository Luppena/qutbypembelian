<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retur_pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('retur_pembelians', 'foto_bukti')) {
                $table->string('foto_bukti')->nullable()->after('alasan_utama');
            }

            if (! Schema::hasColumn('retur_pembelians', 'penyelesaian')) {
                $table->string('penyelesaian')->default('barang_pengganti')->after('keterangan');
            }
        });

        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('draft','menunggu','disetujui','selesai','dibatalkan') DEFAULT 'menunggu'");
        DB::table('retur_pembelians')->where('status', 'draft')->update(['status' => 'menunggu']);
        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('menunggu','disetujui','selesai','dibatalkan') DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('draft','menunggu','disetujui','selesai','dibatalkan') DEFAULT 'draft'");
        DB::table('retur_pembelians')->where('status', 'menunggu')->update(['status' => 'draft']);
        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('draft','disetujui','selesai','dibatalkan') DEFAULT 'draft'");

        Schema::table('retur_pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('retur_pembelians', 'foto_bukti')) {
                $table->dropColumn('foto_bukti');
            }

            if (Schema::hasColumn('retur_pembelians', 'penyelesaian')) {
                $table->dropColumn('penyelesaian');
            }
        });
    }
};
