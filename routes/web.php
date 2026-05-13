<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KartuStokPdfController;
use App\Http\Controllers\LaporanPembelianPdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/kartu-stok/pdf', KartuStokPdfController::class)->name('kartu-stok.pdf');
});

Route::middleware(['auth', 'role:finance,super_admin'])->group(function () {
    Route::get('/admin/laporan-pembelian/pdf', LaporanPembelianPdfController::class)->name('laporan-pembelian.pdf');
    Route::get('/admin/laporan-pembelian/excel', [LaporanPembelianPdfController::class, 'excel'])->name('laporan-pembelian.excel');
    Route::redirect('/admin/laporan/pembelian', '/admin/laporan-pembelian')->name('laporan.pembelian');
});
