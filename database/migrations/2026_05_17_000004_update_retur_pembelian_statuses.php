<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('menunggu','menunggu_pickup','tukar_barang_diproses','tukar_barang_selesai','refund_diproses','refund_selesai','disetujui','selesai','selesai_retur','dibatalkan') DEFAULT 'tukar_barang_diproses'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE retur_pembelians MODIFY status ENUM('menunggu','menunggu_pickup','disetujui','selesai','selesai_retur','dibatalkan') DEFAULT 'menunggu_pickup'");
    }
};
