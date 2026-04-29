<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faktur_pembelians', function (Blueprint $table) {
            $table->id();

            $table->string('no_faktur')->unique();
            $table->date('tanggal_faktur');

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnDelete();

            $table->date('jatuh_tempo')->nullable();

            // ✅ TAMBAHAN: simpan detail faktur dalam JSON
            $table->json('detail')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('dpp', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('total_faktur', 15, 2)->default(0);

            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktur_pembelians');
    }
};
