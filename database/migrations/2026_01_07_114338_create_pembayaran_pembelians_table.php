<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_pembelians', function (Blueprint $table) {

            // Tanggal pembayaran
            $table->date('tanggal_pembayaran')->after('id');

            // Relasi ke faktur pembelian
            $table->foreignId('faktur_pembelian_id')
                ->constrained('faktur_pembelians')
                ->cascadeOnDelete();

            // Relasi ke vendor
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnDelete();

            // Data bank & pembayaran
            $table->string('bank');
            $table->string('no_rekening');
            $table->decimal('nilai_pembayaran', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_pembelians', function (Blueprint $table) {

            $table->dropForeign(['faktur_pembelian_id']);
            $table->dropForeign(['vendor_id']);

            $table->dropColumn([
                'tanggal_pembayaran',
                'faktur_pembelian_id',
                'vendor_id',
                'bank',
                'no_rekening',
                'nilai_pembayaran',
            ]);
        });
    }
};
