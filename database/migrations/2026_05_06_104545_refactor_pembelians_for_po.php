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
        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('syarat_pembayaran')->nullable()->after('vendor_id');
            $table->string('referensi_pr')->nullable()->after('syarat_pembayaran');
            $table->text('catatan_vendor')->nullable()->after('referensi_pr');
            
            // Assuming 'status' is currently an enum or string ('lunas', 'belum_lunas').
            // We change it to string to support the new values.
            $table->string('status')->default('menunggu')->change();
        });

        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('harga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn(['syarat_pembayaran', 'referensi_pr', 'catatan_vendor']);
            $table->string('status')->default('belum_lunas')->change();
        });

        Schema::table('pembelian_details', function (Blueprint $table) {
            $table->dropColumn('diskon_persen');
        });
    }
};
