<?php

namespace App\Filament\Widgets;

use App\Models\Grn;
use App\Models\Pembelian;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GudangStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        $userRole = auth()->user()->role;
        $userRole = $userRole instanceof \App\Enums\UserRole ? $userRole->value : (string) $userRole;
        return in_array($userRole, ['admin', 'operasional', 'gudang']);
    }

    protected function getStats(): array
    {
        $bulanIni = now()->month;
        $tahunIni = now()->year;

        // 1. PO Bulan Ini
        $totalPoBulanIni = Pembelian::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->count();
        $draftPo = Pembelian::whereMonth('tanggal', $bulanIni)->where('status', 'menunggu')->count();
        $aktifPo = $totalPoBulanIni - $draftPo;

        // 2. Nilai Pembelian
        $nilaiPembelian = Pembelian::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->where('status', '!=', 'dibatalkan')
            ->sum('total_akhir');

        // 3. Menunggu GRN
        $menungguGrn = Pembelian::whereIn('status', ['menunggu', 'partial'])->count();

        // 4. Barang Masuk Hari Ini
        $terimaHariIni = Grn::where('status', 'dikonfirmasi')
            ->whereDate('tanggal_terima', now()->toDateString())
            ->count();

        return [
            Stat::make('PO Bulan Ini', $totalPoBulanIni . ' PO')
                ->description($draftPo . ' menunggu · ' . $aktifPo . ' aktif')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Nilai Pembelian', 'Rp ' . number_format($nilaiPembelian, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Menunggu GRN', $menungguGrn . ' PO')
                ->description('Perlu verifikasi penerimaan')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Barang Masuk Hari Ini', $terimaHariIni . ' kiriman')
                ->description('Sudah diterima & dikonfirmasi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
