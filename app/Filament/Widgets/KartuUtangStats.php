<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class KartuUtangStats extends BaseWidget
{
    public ?array $tableFilters = null; // untuk menerima filter dari page

    protected function getStats(): array
    {
        $vendorId = $this->tableFilters['vendor_id'] ?? null;
        $from     = $this->tableFilters['periode']['from'] ?? null;
        $until    = $this->tableFilters['periode']['until'] ?? null;

        // Total saldo utang = SUM saldo terakhir per vendor (pakai subquery)
        $vendorsQuery = Vendor::query();
        if ($vendorId) $vendorsQuery->where('id', $vendorId);

        $saldoTotal = (clone $vendorsQuery)
            ->selectSub(function ($q) {
                $q->from('kartu_utangs')
                    ->select('saldo')
                    ->whereColumn('kartu_utangs.vendor_id', 'vendors.id')
                    ->orderByDesc('tanggal')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'saldo_utang')
            ->get()
            ->sum(fn ($v) => (int) ($v->saldo_utang ?? 0));

        // Total pembayaran periode
        $payQuery = DB::table('pembayaran_pembelians');
        if ($vendorId) $payQuery->where('vendor_id', $vendorId);
        if ($from) $payQuery->whereDate('tanggal_pembayaran', '>=', $from);
        if ($until) $payQuery->whereDate('tanggal_pembayaran', '<=', $until);

        $pembayaranTotal = (int) ($payQuery->sum('nilai_pembayaran') ?? 0);

        $jumlahVendor = (clone $vendorsQuery)->count();

        return [
            Stat::make('Total Saldo Utang', 'Rp ' . number_format($saldoTotal, 0, ',', '.')),
            Stat::make('Pembayaran pada Periode', 'Rp ' . number_format($pembayaranTotal, 0, ',', '.')),
            Stat::make('Jumlah Vendor', $jumlahVendor . ' Vendor'),
        ];
    }
}