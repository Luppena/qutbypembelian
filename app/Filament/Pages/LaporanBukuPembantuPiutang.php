<?php

namespace App\Filament\Pages;

use App\Models\Pelanggan;
use App\Models\Piutang;
use App\Models\Pembayaran;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

// 🔥 PAKAI SCHEMA (BUKAN FORM)
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class LaporanBukuPembantuPiutang extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use \App\Filament\Traits\HasRoleAccess;

    protected static array $allowedRoles = ['finance'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static UnitEnum|string|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Buku Pembantu Piutang';

    protected string $view = 'filament.pages.laporan-buku-pembantu-piutang';

    // 🔥 STATE FILTER
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => null,
        ]);
    }

    // =====================================
    // 🔥 SCHEMA FILTER
    // =====================================
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter')
                    ->schema([
                        Grid::make(1)->schema([
                            Select::make('customer_id')
                                ->label('Pilih Pelanggan')
                                ->options(Pelanggan::pluck('nama_pelanggan', 'id'))
                                ->searchable()
                                ->placeholder('Semua Pelanggan')
                                ->live(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    // =====================================
    // 🔥 HELPER: Baris debit dari piutang
    // =====================================
    /**
     * @param  \App\Models\Pelanggan  $customer
     * @return Collection<int, array<string, mixed>>
     */
    private function getRowsDebit(Pelanggan $customer): Collection
    {
        return Piutang::where('pelanggan_id', $customer->id)
            ->get()
            ->map(function (Piutang $item): array {
                return [
                    'tanggal'    => $item->tanggal_faktur,
                    'ref'        => $item->no_faktur,
                    'keterangan' => 'Penjualan Kredit',
                    'debit'      => (float) $item->total_piutang,
                    'kredit'     => 0.0,
                    'urutan'     => 1,
                ];
            });
    }

    // =====================================
    // 🔥 HELPER: Baris kredit dari pembayaran
    // =====================================
    /**
     * @param  \App\Models\Pelanggan  $customer
     * @return Collection<int, array<string, mixed>>
     */
    private function getRowsKredit(Pelanggan $customer): Collection
    {
        return Pembayaran::with(['piutang'])
            ->whereHas('piutang', function ($q) use ($customer): void {
                $q->where('pelanggan_id', $customer->id);
            })
            ->where('keterangan', 'lunas')
            ->get()
            ->map(function (Pembayaran $item): array {
                return [
                    'tanggal'    => $item->tanggal_bayar,
                    'ref'        => optional($item->piutang)->no_faktur ?? '-',
                    'keterangan' => 'Pelunasan Piutang',
                    'debit'      => 0.0,
                    'kredit'     => (float) $item->jumlah_bayar,
                    'urutan'     => 2,
                ];
            });
    }

    // =====================================
    // 🔥 HELPER: Hitung saldo & susun data customer
    // =====================================
    /**
     * @param  \App\Models\Pelanggan  $customer
     * @return array<string, mixed>|null
     */
    private function buildCustomerLaporan(Pelanggan $customer): ?array
    {
        /** @var Collection<int, array<string, mixed>> $transaksi */
        $transaksi = $this->getRowsDebit($customer)
            ->merge($this->getRowsKredit($customer))
            ->sortBy([
                ['tanggal', 'asc'],
                ['urutan', 'asc'],
            ])
            ->values();

        $saldo       = 0.0;
        $totalDebit  = 0.0;
        $totalKredit = 0.0;
        $rows        = [];

        foreach ($transaksi as $t) {
            $saldo      += $t['debit'];
            $saldo      -= $t['kredit'];
            $totalDebit  += $t['debit'];
            $totalKredit += $t['kredit'];

            $t['saldo'] = $saldo;
            $rows[]     = $t;
        }

        if (count($rows) === 0) {
            return null;
        }

        return [
            'customer'    => $customer->nama_pelanggan,
            'data'        => $rows,
            'total_debit' => $totalDebit,
            'total_kredit'=> $totalKredit,
            'saldo_akhir' => $saldo,
            'status'      => $saldo <= 0 ? 'Lunas' : 'Belum Lunas',
        ];
    }

    // =====================================
    // 🔥 HELPER: Ambil daftar customer sesuai filter
    // =====================================
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Pelanggan>
     */
    private function getFilteredCustomers(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var array<string, mixed> $state */
        $state      = $this->form->getState();
        $customerId = $state['customer_id'] ?? null;

        return $customerId
            ? Pelanggan::where('id', $customerId)->get()
            : Pelanggan::all();
    }

    // =====================================
    // 🔥 DATA LAPORAN
    // =====================================
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLaporanProperty(): array
    {
        $laporan = [];

        foreach ($this->getFilteredCustomers() as $customer) {
            $row = $this->buildCustomerLaporan($customer);
            if ($row !== null) {
                $laporan[] = $row;
            }
        }

        return $laporan;
    }

    // =====================================
    // 🔥 EXPORT PDF
    // =====================================
    public function exportPdf(): StreamedResponse
    {
        $laporan = [];

        foreach ($this->getFilteredCustomers() as $customer) {
            $row = $this->buildCustomerLaporan($customer);
            if ($row !== null) {
                $laporan[] = $row;
            }
        }

        $pdf = Pdf::loadView('exports.buku-pembantu-piutang', compact('laporan'));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'buku-pembantu-piutang.pdf'
        );
    }
}