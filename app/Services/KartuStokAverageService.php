<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\KartuStokAverage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KartuStokAverageService
{
    public function getSaldoSaatIni(int $barangId): array
    {
        $last = KartuStokAverage::query()
            ->where('barang_id', $barangId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        return [
            'sisa_unit' => (int) ($last?->sisa_unit ?? 0),
            'harga_rata_rata' => (float) ($last?->harga_rata_rata ?? 0),
            'nilai_persediaan' => (float) ($last?->nilai_persediaan ?? 0),
        ];
    }

    public function hitungRataRata(int $barangId, int $qtyBeli, float $hargaBeli): float
    {
        $saldo = $this->getSaldoSaatIni($barangId);
        $nilaiLama = (float) $saldo['nilai_persediaan'];
        $unitLama = (int) $saldo['sisa_unit'];
        $nilaiMasuk = $qtyBeli * $hargaBeli;
        $unitBaru = $unitLama + $qtyBeli;

        return $unitBaru > 0
            ? round(($nilaiLama + $nilaiMasuk) / $unitBaru, 2)
            : 0;
    }

    public function previewPembelian(int $barangId, int $qty, float $hargaBeli): array
    {
        $saldo = $this->getSaldoSaatIni($barangId);
        $nilaiMasuk = $qty * $hargaBeli;
        $hargaBaru = $this->hitungRataRata($barangId, $qty, $hargaBeli);
        $stokSetelah = (int) $saldo['sisa_unit'] + $qty;

        return [
            'harga_rata_rata_saat_ini' => (float) $saldo['harga_rata_rata'],
            'nilai_masuk' => $nilaiMasuk,
            'harga_rata_rata_baru' => $hargaBaru,
            'stok_setelah' => $stokSetelah,
        ];
    }

    public function previewPenjualan(int $barangId, int $qty): array
    {
        $saldo = $this->getSaldoSaatIni($barangId);
        $hppPerUnit = (float) $saldo['harga_rata_rata'];
        $stokSetelah = (int) $saldo['sisa_unit'] - $qty;

        return [
            'stok_saat_ini' => (int) $saldo['sisa_unit'],
            'hpp_per_unit' => $hppPerUnit,
            'total_hpp' => $qty * $hppPerUnit,
            'stok_setelah' => $stokSetelah,
        ];
    }

    public function tambahPembelian(int $barangId, string $tanggal, int $qty, float $hargaBeli, ?string $keterangan = null): KartuStokAverage
    {
        return DB::transaction(function () use ($barangId, $tanggal, $qty, $hargaBeli, $keterangan) {
            $saldo = $this->getSaldoSaatIni($barangId);
            $hargaRataRata = $this->hitungRataRata($barangId, $qty, $hargaBeli);
            $sisaUnit = (int) $saldo['sisa_unit'] + $qty;
            $nilaiPersediaan = round($sisaUnit * $hargaRataRata, 2);

            $row = KartuStokAverage::create([
                'barang_id' => $barangId,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan ?: 'Pembelian',
                'jenis' => 'beli',
                'qty' => $qty,
                'harga_beli' => $hargaBeli,
                'hpp_per_unit' => 0,
                'hpp_total' => 0,
                'sisa_unit' => $sisaUnit,
                'harga_rata_rata' => $hargaRataRata,
                'nilai_persediaan' => $nilaiPersediaan,
            ]);

            $this->rebuildBarang($barangId);
            Barang::whereKey($barangId)->increment('stok', $qty);

            return $row;
        });
    }

    public function tambahPenjualan(int $barangId, string $tanggal, int $qty, ?string $keterangan = null): KartuStokAverage
    {
        return DB::transaction(function () use ($barangId, $tanggal, $qty, $keterangan) {
            $saldo = $this->getSaldoSaatIni($barangId);

            if ($qty > (int) $saldo['sisa_unit']) {
                throw new \InvalidArgumentException('Qty melebihi stok tersedia (maks. ' . (int) $saldo['sisa_unit'] . ' unit)');
            }

            $hppPerUnit = (float) $saldo['harga_rata_rata'];
            $sisaUnit = (int) $saldo['sisa_unit'] - $qty;

            $row = KartuStokAverage::create([
                'barang_id' => $barangId,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan ?: 'Penjualan',
                'jenis' => 'jual',
                'qty' => $qty,
                'harga_beli' => 0,
                'hpp_per_unit' => $hppPerUnit,
                'hpp_total' => round($qty * $hppPerUnit, 2),
                'sisa_unit' => $sisaUnit,
                'harga_rata_rata' => $hppPerUnit,
                'nilai_persediaan' => round($sisaUnit * $hppPerUnit, 2),
            ]);

            $this->rebuildBarang($barangId);
            Barang::whereKey($barangId)->decrement('stok', $qty);

            return $row;
        });
    }

    public function rebuildBarang(int $barangId): void
    {
        $sisaUnit = 0;
        $hargaRataRata = 0.0;
        $nilaiPersediaan = 0.0;

        KartuStokAverage::query()
            ->where('barang_id', $barangId)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->each(function (KartuStokAverage $entry) use (&$sisaUnit, &$hargaRataRata, &$nilaiPersediaan) {
                if ($entry->jenis === 'beli' || $entry->jenis === 'awal') {
                    $nilaiMasuk = (int) $entry->qty * (float) $entry->harga_beli;
                    $unitBaru = $sisaUnit + (int) $entry->qty;
                    $hargaRataRata = $unitBaru > 0
                        ? round(($nilaiPersediaan + $nilaiMasuk) / $unitBaru, 2)
                        : 0;
                    $sisaUnit = $unitBaru;
                    $nilaiPersediaan = round($sisaUnit * $hargaRataRata, 2);

                    $entry->forceFill([
                        'hpp_per_unit' => 0,
                        'hpp_total' => 0,
                        'sisa_unit' => $sisaUnit,
                        'harga_rata_rata' => $hargaRataRata,
                        'nilai_persediaan' => $nilaiPersediaan,
                    ])->saveQuietly();

                    return;
                }

                $hppTotal = round((int) $entry->qty * $hargaRataRata, 2);
                $sisaUnit -= (int) $entry->qty;
                $nilaiPersediaan = round($sisaUnit * $hargaRataRata, 2);

                $entry->forceFill([
                    'harga_beli' => 0,
                    'hpp_per_unit' => $hargaRataRata,
                    'hpp_total' => $hppTotal,
                    'sisa_unit' => $sisaUnit,
                    'harga_rata_rata' => $hargaRataRata,
                    'nilai_persediaan' => $nilaiPersediaan,
                ])->saveQuietly();
            });
    }

    public function getCards(string $bulan, string $tahun, ?int $barangId = null): array
    {
        $tglMulai = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->startOfMonth();
        $tglAkhir = $tglMulai->copy()->endOfMonth();

        $barangIds = KartuStokAverage::query()
            ->where('tanggal', '<=', $tglAkhir->format('Y-m-d'))
            ->when($barangId, fn ($query) => $query->where('barang_id', $barangId))
            ->distinct()
            ->pluck('barang_id');

        if ($barangId && ! $barangIds->contains($barangId)) {
            $barangIds->push($barangId);
        }

        return Barang::query()
            ->whereIn('id', $barangIds)
            ->orderBy('nama_barang')
            ->get()
            ->map(fn (Barang $barang) => $this->buildCard($barang, $tglMulai, $tglAkhir))
            ->filter(fn (array $card) => $card['rows'] !== [] || $card['saldo_awal_unit'] > 0)
            ->values()
            ->all();
    }

    public function getSummary(string $bulan, string $tahun, ?int $barangId = null): array
    {
        $cards = $this->getCards($bulan, $tahun, $barangId);

        return [
            'total_pembelian' => collect($cards)->sum('total_pembelian'),
            'total_hpp' => collect($cards)->sum('total_hpp'),
            'nilai_persediaan_akhir' => collect($cards)->sum('persediaan_akhir'),
            'metode' => 'Average',
        ];
    }

    private function buildCard(Barang $barang, Carbon $tglMulai, Carbon $tglAkhir): array
    {
        $entries = KartuStokAverage::query()
            ->where('barang_id', $barang->id)
            ->where('tanggal', '<=', $tglAkhir->format('Y-m-d'))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldoAwal = $this->saldoAwal($entries, $tglMulai);
        $periodRows = $entries
            ->filter(fn (KartuStokAverage $entry) => Carbon::parse($entry->tanggal)->betweenIncluded($tglMulai, $tglAkhir))
            ->map(fn (KartuStokAverage $entry) => $this->formatRow($entry))
            ->values()
            ->all();

        $rows = [];

        if ($saldoAwal['sisa_unit'] > 0 || $saldoAwal['nilai_persediaan'] > 0) {
            $rows[] = [
                'tanggal' => $tglMulai->format('d/m/Y'),
                'keterangan' => 'Saldo Awal',
                'jenis' => 'awal',
                'pembelian' => null,
                'hpp' => null,
                'persediaan' => [
                    'qty' => $saldoAwal['sisa_unit'],
                    'harga' => $saldoAwal['harga_rata_rata'],
                    'total' => $saldoAwal['nilai_persediaan'],
                    'average_changed' => false,
                ],
            ];
        }

        $rows = array_merge($rows, $periodRows);
        $last = $entries->last();
        $totalPembelianUnit = collect($periodRows)->sum(fn (array $row) => (int) ($row['pembelian']['qty'] ?? 0));
        $totalPembelian = collect($periodRows)->sum(fn (array $row) => (float) ($row['pembelian']['total'] ?? 0));
        $totalJualUnit = collect($periodRows)->sum(fn (array $row) => (int) ($row['hpp']['qty'] ?? 0));
        $totalHpp = collect($periodRows)->sum(fn (array $row) => (float) ($row['hpp']['total'] ?? 0));
        $persediaanAkhir = (float) ($last?->nilai_persediaan ?? 0);

        return [
            'barang' => $barang,
            'rows' => $rows,
            'saldo_awal_unit' => $saldoAwal['sisa_unit'],
            'saldo_awal_nilai' => $saldoAwal['nilai_persediaan'],
            'total_pembelian_unit' => $totalPembelianUnit,
            'total_pembelian' => $totalPembelian,
            'total_jual_unit' => $totalJualUnit,
            'total_hpp' => $totalHpp,
            'stok_akhir' => (int) ($last?->sisa_unit ?? 0),
            'harga_rata_rata_akhir' => (float) ($last?->harga_rata_rata ?? 0),
            'persediaan_akhir' => $persediaanAkhir,
            'validasi' => round($saldoAwal['nilai_persediaan'] + $totalPembelian - $totalHpp, 2),
            'valid' => abs(($saldoAwal['nilai_persediaan'] + $totalPembelian - $totalHpp) - $persediaanAkhir) <= 1,
        ];
    }

    private function saldoAwal(Collection $entries, Carbon $tglMulai): array
    {
        $lastBeforePeriod = $entries
            ->filter(fn (KartuStokAverage $entry) => Carbon::parse($entry->tanggal)->lt($tglMulai))
            ->last();

        return [
            'sisa_unit' => (int) ($lastBeforePeriod?->sisa_unit ?? 0),
            'harga_rata_rata' => (float) ($lastBeforePeriod?->harga_rata_rata ?? 0),
            'nilai_persediaan' => (float) ($lastBeforePeriod?->nilai_persediaan ?? 0),
        ];
    }

    private function formatRow(KartuStokAverage $entry): array
    {
        $pembelian = null;
        $hpp = null;

        if ($entry->jenis === 'beli') {
            $pembelian = [
                'qty' => (int) $entry->qty,
                'harga' => (float) $entry->harga_beli,
                'total' => (int) $entry->qty * (float) $entry->harga_beli,
            ];
        }

        if ($entry->jenis === 'jual') {
            $hpp = [
                'qty' => (int) $entry->qty,
                'harga' => (float) $entry->hpp_per_unit,
                'total' => (float) $entry->hpp_total,
            ];
        }

        return [
            'tanggal' => $entry->tanggal?->format('d/m/Y') ?? '-',
            'keterangan' => $entry->keterangan,
            'jenis' => $entry->jenis,
            'pembelian' => $pembelian,
            'hpp' => $hpp,
            'persediaan' => [
                'qty' => (int) $entry->sisa_unit,
                'harga' => (float) $entry->harga_rata_rata,
                'total' => (float) $entry->nilai_persediaan,
                'average_changed' => $entry->jenis === 'beli',
            ],
        ];
    }
}
