<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->date('estimasi_datang')->nullable()->after('status');
            $table->enum('status_pengiriman', ['menunggu', 'dijadwalkan', 'dalam_kirim'])
                  ->default('menunggu')->after('estimasi_datang');
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn(['estimasi_datang', 'status_pengiriman']);
        });
    }
};
