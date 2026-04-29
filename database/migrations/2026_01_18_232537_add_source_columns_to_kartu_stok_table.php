<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kartu_stok', function (Blueprint $table) {
            $table->string('source_type')->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->unsignedBigInteger('source_line_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('kartu_stok', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'source_line_id']);
        });
    }
};
