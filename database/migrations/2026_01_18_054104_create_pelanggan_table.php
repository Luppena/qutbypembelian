<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED
            $table->string('nama');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
