<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->string('status_penerimaan')->default('lengkap')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->dropColumn('status_penerimaan');
        });
    }
};
