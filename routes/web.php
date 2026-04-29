<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\KartuUtangPdfController;
use App\Http\Controllers\LaporanPembelianPdfController;

// Route untuk halaman utama (welcome)
Route::get('/', function () {
    return view('welcome');
});

// Rute untuk menampilkan detail pembelian
Route::get('/admin/pembelian/{pembelian}', [PembelianController::class, 'show'])
    ->name('filament.admin.resources.pembelian.pembelians.view');

// Laporan pembelian PDF
Route::get('/admin/laporan-pembelian/pdf', LaporanPembelianPdfController::class)
    ->name('laporan-pembelian.pdf')
    ->middleware(['auth']);

// Kartu Utang PDF
Route::get('/admin/kartu-utang/pdf', KartuUtangPdfController::class)
    ->name('kartu-utang.pdf')
    ->middleware(['auth']);

// Kartu Stok PDF
Route::get('/admin/kartu-stok/pdf', \App\Http\Controllers\KartuStokPdfController::class)
    ->name('kartu-stok.pdf')
    ->middleware(['auth']);
