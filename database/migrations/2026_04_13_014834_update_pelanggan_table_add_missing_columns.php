<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->string('kode_pelanggan', 20)->unique()->nullable()->after('id');
            $table->renameColumn('nama', 'nama_pelanggan');
            $table->renameColumn('no_hp', 'no_telp');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn('kode_pelanggan');
            $table->renameColumn('nama_pelanggan', 'nama');
            $table->renameColumn('no_telp', 'no_hp');
        });
    }
};
