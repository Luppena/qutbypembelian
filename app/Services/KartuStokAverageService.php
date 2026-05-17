<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\GrnDetail;
use App\Models\KartuStok;
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

        $barangIds = GrnDetail::query()
            ->when($barangId, fn ($query) => $query->where('barang_id', $barangId))
            ->whereHas('grn', fn ($query) => $query
                ->where('status', 'dikonfirmasi')
                ->whereDate('tanggal_terima', '<=', $tglAkhir->format('Y-m-d')))
            ->where('qty_diterima', '>', 0)
            ->where('kondisi', 'baik')
            ->whereRaw('(qty_diterima - COALESCE(qty_rusak, 0)) > 0')
            ->distinct()
            ->pluck('barang_id');

        $keluarBarangIds = KartuStok::query()
            ->when($barangId, fn ($query) => $query->where('barang_id', $barangId))
            ->whereNotNull('barang_id')
            ->where('keluar', '>', 0)
            ->whereDate('tanggal', '<=', $tglAkhir->format('Y-m-d'))
            ->distinct()
            ->pluck('barang_id');

        $barangIds = $barangIds->merge($keluarBarangIds)->unique()->values();

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
        $events = $this->getBarangMasukAverageEvents($barang->id, $tglAkhir)
            ->concat($this->getBarangKeluarAverageEvents($barang->id, $tglAkhir))
            ->sortBy([
                ['tanggal_sort', 'asc'],
                ['urut', 'asc'],
            ])
            ->values();

        $saldo = [
            'unit' => 0,
            'harga' => 0.0,
            'nilai' => 0.0,
        ];

        $events
            ->filter(fn (array $event) => Carbon::parse($event['tanggal'])->lt($tglMulai))
            ->each(fn (array $event) => $this->applyAverageEvent($saldo, $event));

        $rows = [];
        $saldoAwal = [
            'sisa_unit' => $saldo['unit'],
            'harga_rata_rata' => $saldo['harga'],
            'nilai_persediaan' => $saldo['nilai'],
        ];

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

        $periodRows = $events
            ->filter(fn (array $event) => Carbon::parse($event['tanggal'])->betweenIncluded($tglMulai, $tglAkhir))
            ->map(function (array $event) use (&$saldo): array {
                return $this->formatAverageEventRow($saldo, $event);
            })
            ->values()
            ->all();

        $rows = array_merge($rows, $periodRows);
        $totalPembelianUnit = collect($periodRows)->sum(fn (array $row) => (int) ($row['pembelian']['qty'] ?? 0));
        $totalPembelian = collect($periodRows)->sum(fn (array $row) => (float) ($row['pembelian']['total'] ?? 0));
        $totalJualUnit = collect($periodRows)->sum(fn (array $row) => (int) ($row['hpp']['qty'] ?? 0));
        $totalHpp = collect($periodRows)->sum(fn (array $row) => (float) ($row['hpp']['total'] ?? 0));
        $persediaanAkhir = (float) $saldo['nilai'];

        return [
            'barang' => $barang,
            'rows' => $rows,
            'saldo_awal_unit' => $saldoAwal['sisa_unit'],
            'saldo_awal_nilai' => $saldoAwal['nilai_persediaan'],
            'total_pembelian_unit' => $totalPembelianUnit,
            'total_pembelian' => $totalPembelian,
            'total_jual_unit' => $totalJualUnit,
            'total_hpp' => $totalHpp,
            'stok_akhir' => (int) $saldo['unit'],
            'harga_rata_rata_akhir' => (float) $saldo['harga'],
            'persediaan_akhir' => $persediaanAkhir,
            'validasi' => round($saldoAwal['nilai_persediaan'] + $totalPembelian - $totalHpp, 2),
            'valid' => abs(($saldoAwal['nilai_persediaan'] + $totalPembelian - $totalHpp) - $persediaanAkhir) <= 1,
        ];
    }

    private function getBarangMasukAverageEvents(int $barangId, Carbon $tglAkhir): Collection
    {
        return GrnDetail::query()
            ->with(['grn.pembelian', 'pembelianDetail'])
            ->where('barang_id', $barangId)
            ->whereHas('grn', fn ($query) => $query
                ->where('status', 'dikonfirmasi')
                ->whereDate('tanggal_terima', '<=', $tglAkhir->format('Y-m-d')))
            ->where('qty_diterima', '>', 0)
            ->where('kondisi', 'baik')
            ->whereRaw('(qty_diterima - COALESCE(qty_rusak, 0)) > 0')
            ->orderBy('id')
            ->get()
            ->map(function (GrnDetail $detail): array {
                $tanggal = $detail->grn?->tanggal_terima
                    ? Carbon::parse($detail->grn->tanggal_terima)
                    : Carbon::parse('2000-01-01');
                $qty = max(0, (int) $detail->qty_diterima - (int) ($detail->qty_rusak ?? 0));
                $harga = (float) ($detail->pembelianDetail?->harga ?? 0);
                $po = $detail->grn?->pembelian?->nomor;

                return [
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'tanggal_label' => $tanggal->format('d/m/Y'),
                    'tanggal_sort' => $tanggal->format('Y-m-d'),
                    'urut' => $detail->id,
                    'jenis' => 'beli',
                    'qty' => $qty,
                    'harga' => $harga,
                    'keterangan' => $po ? 'No. PO ' . $po : 'GRN ' . ($detail->grn?->nomor_grn ?? '-'),
                ];
            });
    }

    private function getBarangKeluarAverageEvents(int $barangId, Carbon $tglAkhir): Collection
    {
        return KartuStok::query()
            ->where('barang_id', $barangId)
            ->where('keluar', '>', 0)
            ->whereDate('tanggal', '<=', $tglAkhir->format('Y-m-d'))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(function (KartuStok $entry): array {
                $tanggal = $entry->tanggal
                    ? Carbon::parse($entry->tanggal)
                    : Carbon::parse('2000-01-01');

                return [
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'tanggal_label' => $tanggal->format('d/m/Y'),
                    'tanggal_sort' => $tanggal->format('Y-m-d'),
                    'urut' => 100000000 + (int) $entry->id,
                    'jenis' => 'jual',
                    'qty' => (int) $entry->keluar,
                    'harga' => 0,
                    'keterangan' => filled($entry->keterangan)
                        ? trim((string) preg_replace('/\s+\(.+\)$/', '', (string) $entry->keterangan))
                        : 'Barang Keluar',
                ];
            });
    }

    private function formatAverageEventRow(array &$saldo, array $event): array
    {
        $pembelian = null;
        $hpp = null;
        $hargaLama = (float) $saldo['harga'];

        if ($event['jenis'] === 'beli') {
            $totalMasuk = (int) $event['qty'] * (float) $event['harga'];
            $unitBaru = (int) $saldo['unit'] + (int) $event['qty'];
            $hargaBaru = $unitBaru > 0
                ? round(((float) $saldo['nilai'] + $totalMasuk) / $unitBaru, 2)
                : 0;

            $saldo['unit'] = $unitBaru;
            $saldo['harga'] = $hargaBaru;
            $saldo['nilai'] = round($unitBaru * $hargaBaru, 2);

            $pembelian = [
                'qty' => (int) $event['qty'],
                'harga' => (float) $event['harga'],
                'total' => $totalMasuk,
            ];
        }

        if ($event['jenis'] === 'jual') {
            $qtyKeluar = (int) $event['qty'];
            $hppTotal = round($qtyKeluar * $hargaLama, 2);

            $saldo['unit'] = (int) $saldo['unit'] - $qtyKeluar;
            $saldo['nilai'] = round((int) $saldo['unit'] * $hargaLama, 2);

            $hpp = [
                'qty' => $qtyKeluar,
                'harga' => $hargaLama,
                'total' => $hppTotal,
            ];
        }

        return [
            'tanggal' => $event['tanggal_label'],
            'keterangan' => $event['keterangan'],
            'jenis' => $event['jenis'],
            'pembelian' => $pembelian,
            'hpp' => $hpp,
            'persediaan' => [
                'qty' => (int) $saldo['unit'],
                'harga' => (float) $saldo['harga'],
                'total' => (float) $saldo['nilai'],
                'average_changed' => $event['jenis'] === 'beli',
            ],
        ];
    }

    private function applyAverageEvent(array &$saldo, array $event): void
    {
        $this->formatAverageEventRow($saldo, $event);
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
