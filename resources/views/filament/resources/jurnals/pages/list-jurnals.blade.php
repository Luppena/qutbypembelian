<x-filament-panels::page>
    @php
        $jurnals      = $this->getJurnals();
        $periodeLabel = $this->getPeriodeLabel();
        $bulan        = $this->bulan;
        $tahun        = $this->tahun;
    @endphp

    {{-- FILTER BAR --}}
    <x-filament::section>
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            {{-- Filter Periode --}}
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-size:13px; font-weight:600; color:#374151; white-space:nowrap;">Periode:</span>

                <select wire:model.live="bulan"
                        style="border:1px solid #d1d5db; border-radius:8px; padding:6px 10px;
                               font-size:13px; color:#111827; background:#fff; cursor:pointer;
                               height:36px; min-width:120px;">
                    @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
                               '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
                               '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($bulan === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <select wire:model.live="tahun"
                        style="border:1px solid #d1d5db; border-radius:8px; padding:6px 10px;
                               font-size:13px; color:#111827; background:#fff; cursor:pointer;
                               height:36px; min-width:80px;">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" @selected((int)$tahun === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Tombol Cetak --}}
            <x-filament::button color="gray" onclick="window.print()" class="no-print">
                <x-slot name="icon">
                    <x-heroicon-m-printer class="h-4 w-4" />
                </x-slot>
                Cetak
            </x-filament::button>
        </div>
    </x-filament::section>


    {{-- TABEL JURNAL --}}
    <x-filament::section id="print-area">
        {{-- Kop Cetak --}}
        <div class="mb-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">CV Qutby Creativindo</p>
            <h2 class="mt-0.5 text-base font-bold text-gray-900 dark:text-white">Jurnal Umum</h2>
            <p class="text-xs text-gray-500">{{ $periodeLabel }}</p>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                {{-- Header --}}
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="border-b border-gray-200 px-3 py-3 text-center text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-10">No</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-28">Tanggal</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400">Keterangan</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-center text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-24">Ref</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-36">Debit (Rp)</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-36">Kredit (Rp)</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-center text-xs font-semibold text-gray-600
                                   dark:border-gray-700 dark:text-gray-400 w-16 no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @if($jurnals->isEmpty())
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">
                                Belum ada data jurnal untuk periode ini.
                            </td>
                        </tr>
                    @else
                        @foreach($jurnals as $no => $jurnal)
                            @php
                                $editUrl     = $this->getEditUrl($jurnal);
                                $detailCount = $jurnal->details->count();
                            @endphp

                            @foreach($jurnal->details as $idx => $detail)
                                @php
                                    $isDebit = $detail->debit > 0;
                                    $isFirst = $idx === 0;
                                    $isLast  = $idx === $detailCount - 1;
                                @endphp
                                <tr class="{{ $isLast && !$loop->parent->last ? 'border-b-2 border-gray-200 dark:border-gray-700' : '' }}
                                            hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">

                                    {{-- No --}}
                                    @if($isFirst)
                                    <td class="px-3 py-3 text-center text-xs text-gray-500 dark:text-gray-400 align-top"
                                        @if($detailCount > 1) rowspan="{{ $detailCount }}" @endif>
                                        {{ $no + 1 }}
                                    </td>
                                    @endif

                                    {{-- Tanggal --}}
                                    @if($isFirst)
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap align-top"
                                        @if($detailCount > 1) rowspan="{{ $detailCount }}" @endif>
                                        {{ \Carbon\Carbon::parse($jurnal->tanggal)->translatedFormat('d M Y') }}
                                    </td>
                                    @endif

                                    {{-- Keterangan (Nama Akun) --}}
                                    <td class="px-4 py-2 align-middle">
                                        @if($isDebit)
                                            <span class="font-semibold text-gray-900 dark:text-white text-sm">
                                                {{ $detail->akun->nama_akun ?? '-' }}
                                            </span>
                                        @else
                                            <span class="italic text-gray-500 dark:text-gray-400 text-sm pl-6">
                                                {{ $detail->akun->nama_akun ?? '-' }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Ref (Kode Akun dari Daftar Akun) --}}
                                    <td class="px-4 py-2 text-center align-middle">
                                        @if($detail->akun?->kode_akun)
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5
                                                         text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $detail->akun->kode_akun }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Debit --}}
                                    <td class="px-4 py-2 text-right font-mono text-sm align-middle
                                               text-gray-900 dark:text-white">
                                        @if($isDebit)
                                            {{ number_format($detail->debit, 0, ',', '.') }}
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">–</span>
                                        @endif
                                    </td>

                                    {{-- Kredit --}}
                                    <td class="px-4 py-2 text-right font-mono text-sm align-middle
                                               text-gray-900 dark:text-white">
                                        @if(!$isDebit && $detail->kredit > 0)
                                            {{ number_format($detail->kredit, 0, ',', '.') }}
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">–</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    @if($isFirst)
                                    <td class="px-4 py-2 text-center align-middle no-print"
                                        @if($detailCount > 1) rowspan="{{ $detailCount }}" @endif>
                                        <a href="{{ $editUrl }}"
                                           class="inline-flex items-center justify-center rounded-lg border border-gray-200
                                                  bg-white p-1.5 text-gray-400 shadow-sm hover:bg-gray-50 hover:text-gray-600
                                                  dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700
                                                  transition-colors"
                                           title="Edit">
                                            <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                        </a>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>

                {{-- Footer Total --}}
                @if($jurnals->isNotEmpty())
                    @php
                        $totalDebit  = $jurnals->flatMap->details->sum('debit');
                        $totalKredit = $jurnals->flatMap->details->sum('kredit');
                        $balance     = $totalDebit - $totalKredit;
                    @endphp
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right text-xs font-semibold uppercase
                                                    tracking-wider text-gray-600 dark:text-gray-400">
                                Total
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-white">
                                {{ number_format($totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-gray-900 dark:text-white">
                                {{ number_format($totalKredit, 0, ',', '.') }}
                            </td>
                            <td class="no-print"></td>
                        </tr>
                        <tr>
                            <td colspan="7" class="px-4 py-3 text-center border-t border-gray-100 dark:border-gray-700">
                                @if($balance == 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1
                                                 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
                                        Jurnal Balance (Debit = Kredit)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1
                                                 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        <x-heroicon-m-exclamation-triangle class="h-3.5 w-3.5" />
                                        Tidak Balance! Selisih: Rp {{ number_format(abs($balance), 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>

    <style>
        @media print {
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area { position: fixed; top: 0; left: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</x-filament-panels::page>
