<x-filament-panels::page>
    @php
        $ledger      = $this->getLedger();
        $periodeLabel = $this->getPeriodeLabel();
        $bulan        = $this->bulan;
        $tahun        = $this->tahun;
        $akunOptions  = $this->getDaftarAkunOptions();
    @endphp

    {{-- ======================== FILTER BAR ======================== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8
                bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/80 shadow-sm">

        <div class="flex flex-wrap items-center gap-3">
            {{-- Icon kalender --}}
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 shrink-0">
                <x-heroicon-m-calendar class="w-5 h-5" />
            </div>
            <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Periode:</span>

            {{-- Pilih Bulan --}}
            <select wire:model.live="bulan"
                class="rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500
                       sm:text-sm bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-colors hover:bg-white cursor-pointer py-2 pl-3 pr-8">
                @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                           '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $v => $l)
                    <option value="{{ $v }}" @selected($bulan === $v)>{{ $l }}</option>
                @endforeach
            </select>

            {{-- Pilih Tahun --}}
            <select wire:model.live="tahun"
                class="rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500
                       sm:text-sm bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-colors hover:bg-white cursor-pointer py-2 pl-3 pr-8">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" @selected((int)$tahun === $y)>{{ $y }}</option>
                @endfor
            </select>

            {{-- Divider --}}
            <div class="hidden sm:block h-7 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

            {{-- Filter Akun --}}
            <div class="flex items-center gap-2">
                <x-heroicon-m-building-library class="w-4 h-4 text-gray-400 shrink-0" />
                <select wire:model.live="daftar_akun_id"
                    class="rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500
                           sm:text-sm bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-colors hover:bg-white cursor-pointer py-2 pl-3 pr-8 min-w-[220px]">
                    <option value="">— Semua Akun —</option>
                    @foreach($akunOptions as $id => $label)
                        <option value="{{ $id }}" @selected((int)$this->daftar_akun_id === (int)$id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

    </div>

    {{-- ======================== AREA CETAK ======================== --}}
    <div id="print-area">

        @if(empty($ledger))
            {{-- Empty state with table --}}
            <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 overflow-hidden shadow-sm bg-white dark:bg-gray-900 mb-6">
                
                {{-- Judul --}}
                <div class="text-center py-6 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800/60 dark:to-gray-900/60 border-b border-gray-100 dark:border-gray-700/50">
                    <h2 class="font-extrabold text-gray-800 dark:text-white uppercase tracking-widest text-lg">CV Qutby Creativindo</h2>
                    <p class="font-bold text-primary-600 dark:text-primary-400 mt-1 text-base">BUKU BESAR</p>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-0.5 bg-gray-100 dark:bg-gray-800 inline-block px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700 mt-2">Periode {{ $periodeLabel }}</p>
                </div>

                {{-- Tabel Kosong --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-primary-600 text-white text-xs uppercase tracking-wider">
                                <th class="px-5 py-3 font-semibold text-left w-[15%]">Tanggal</th>
                                <th class="px-5 py-3 font-semibold text-left w-[30%]">Keterangan</th>
                                <th class="px-5 py-3 font-semibold text-center w-[10%]">Ref</th>
                                <th class="px-5 py-3 font-semibold text-right w-[15%]">Debit</th>
                                <th class="px-5 py-3 font-semibold text-right w-[15%]">Kredit</th>
                                <th class="px-5 py-3 font-semibold text-right w-[15%]">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="py-24 text-center border-b border-gray-100 dark:border-gray-700/50">
                                    <div class="flex flex-col items-center justify-center gap-4">
                                        <div class="p-5 bg-primary-50 dark:bg-gray-800 rounded-full shadow-sm">
                                            <x-heroicon-o-book-open class="w-12 h-12 text-primary-400 dark:text-primary-500" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-700 dark:text-gray-200 text-lg">Belum Ada Data</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                Tidak ada transaksi pada periode <strong>{{ $periodeLabel }}</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @foreach($ledger as $entry)
                @php $akun = $entry['akun']; @endphp

                {{-- Kartu per akun --}}
                <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 overflow-hidden shadow-sm bg-white dark:bg-gray-900 mb-6">

                    {{-- Judul akun --}}
                    <div class="text-center py-6 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800/60 dark:to-gray-900/60 border-b border-gray-100 dark:border-gray-700/50">
                        <h2 class="font-extrabold text-gray-800 dark:text-white uppercase tracking-widest text-lg">CV Qutby Creativindo</h2>
                        <p class="font-bold text-gray-900 dark:text-white mt-1 text-base">BUKU BESAR</p>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-0.5 bg-gray-100 dark:bg-gray-800 inline-block px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700 mt-2">Periode {{ $periodeLabel }}</p>
                    </div>

                    {{-- Label akun --}}
                    <div class="flex items-center gap-3 px-5 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/40">
                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold font-mono tracking-wide">
                            {{ $akun->kode_akun }}
                        </span>
                        <span class="font-bold text-gray-800 dark:text-white text-sm">{{ $akun->nama_akun }}</span>
                        <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 italic">Saldo Normal: {{ ucfirst($akun->saldo_normal ?? 'debit') }}</span>
                    </div>

                    {{-- Tabel --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-primary-600 text-white text-xs uppercase tracking-wider">
                                    <th class="px-5 py-3 font-semibold text-left w-[15%]">Tanggal</th>
                                    <th class="px-5 py-3 font-semibold text-left w-[30%]">Keterangan</th>
                                    <th class="px-5 py-3 font-semibold text-center w-[10%]">Ref</th>
                                    <th class="px-5 py-3 font-semibold text-right w-[15%]">Debit</th>
                                    <th class="px-5 py-3 font-semibold text-right w-[15%]">Kredit</th>
                                    <th class="px-5 py-3 font-semibold text-right w-[15%]">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Baris saldo awal --}}
                                <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="px-5 py-2.5 text-gray-400 dark:text-gray-500 text-xs"></td>
                                    <td class="px-5 py-2.5 font-semibold text-gray-600 dark:text-gray-300 italic text-xs">Saldo Awal</td>
                                    <td class="px-5 py-2.5"></td>
                                    <td class="px-5 py-2.5 text-right font-mono text-xs text-gray-400">-</td>
                                    <td class="px-5 py-2.5 text-right font-mono text-xs text-gray-400">-</td>
                                    <td class="px-5 py-2.5 text-right font-mono font-bold text-gray-700 dark:text-gray-200 text-xs">
                                        Rp {{ number_format($entry['saldo_awal'], 0, ',', '.') }}
                                    </td>
                                </tr>

                                @forelse($entry['rows'] as $i => $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 transition-colors
                                               {{ $i % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/60 dark:bg-gray-800/20' }}
                                               hover:bg-primary-50/40 dark:hover:bg-gray-800/60">
                                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-600 dark:text-gray-400 text-sm font-medium">
                                            {{ \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="px-5 py-2.5 text-gray-700 dark:text-gray-200 text-sm">
                                            {{ $row['keterangan'] }}
                                        </td>
                                        <td class="px-5 py-2.5 text-center">
                                            @if($row['referensi'])
                                                <span class="text-[10px] font-mono bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">
                                                    {{ $row['referensi'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono text-sm">
                                            @if($row['debit'] > 0)
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                                    Rp {{ number_format($row['debit'], 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono text-sm">
                                            @if($row['kredit'] > 0)
                                                <span class="font-bold text-rose-600 dark:text-rose-400">
                                                    Rp {{ number_format($row['kredit'], 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-2.5 text-right font-mono text-sm font-semibold
                                                   {{ $row['saldo'] >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-rose-600 dark:text-rose-400' }}">
                                            Rp {{ number_format($row['saldo'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-400 dark:text-gray-500 text-sm italic">
                                            Tidak ada mutasi pada periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            {{-- Footer total --}}
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800/80 border-t-2 border-gray-200 dark:border-gray-700 font-bold">
                                    <td colspan="3" class="px-5 py-3 text-right text-sm uppercase tracking-wider text-gray-700 dark:text-gray-200">Total Mutasi</td>
                                    <td class="px-5 py-3 text-right font-mono text-emerald-700 dark:text-emerald-400 text-sm">
                                        Rp {{ number_format($entry['total_debit'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-rose-700 dark:text-rose-400 text-sm">
                                        Rp {{ number_format($entry['total_kredit'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-primary-700 dark:text-primary-400 text-sm">
                                        Rp {{ number_format($entry['saldo_akhir'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- CSS Print --}}
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area { position: fixed; top: 0; left: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

</x-filament-panels::page>
