<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PenerimaanBarang;
use App\Observers\PenerimaanBarangObserver;
use App\Models\FakturPembelian;
use App\Models\PembayaranPembelian;
use App\Models\Pembelian;

use App\Observers\PembelianObserver;
use App\Observers\FakturPembelianObserver;
use App\Observers\PembayaranPembelianObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
{
    FakturPembelian::observe(FakturPembelianObserver::class);
    Pembelian::observe(PembelianObserver::class);

    \App\Models\Penjualan::observe(\App\Observers\PenjualanObserver::class);
    PembayaranPembelian::observe(PembayaranPembelianObserver::class);
    PenerimaanBarang::observe(PenerimaanBarangObserver::class);
    \Carbon\Carbon::setLocale('id');
}
}
