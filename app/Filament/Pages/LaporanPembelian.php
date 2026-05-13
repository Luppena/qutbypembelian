<?php

namespace App\Filament\Pages;

class LaporanPembelian extends RekapPembelian
{
    protected static array $allowedRoles = ['finance'];

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.laporan-pembelian';
}
