<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KartuStokPdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/kartu-stok/pdf', KartuStokPdfController::class)->name('kartu-stok.pdf');
});
