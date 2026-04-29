<x-filament-panels::page>
    @php
        $rows        = $this->getLaporanRows();
        $grandTotal  = $this->getGrandTotal($rows);
        $filterLabel = $this->getPeriodeLabel();
        $bulan       = $this->bulan;
        $tahun       = $this->tahun;
        $vendors     = $this->getVendorOptions();
    @endphp

    {{-- ═══════════════════════ FILTER PANEL ═══════════════════════ --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-700/80 rounded-2xl shadow-sm mb-6 overflow-hidden">



        <div class="p-5">
            {{-- ── Input Fields ── --}}
            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">

                {{-- Bulan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Bulan</label>
                    <select wire:model.live="bulan"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-colors cursor-pointer py-2 pl-3 pr-8">
                        @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                                   '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $v => $l)
                            <option value="{{ $v }}" @selected($bulan === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tahun --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Tahun</label>
                    <select wire:model.live="tahun"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-colors cursor-pointer py-2 pl-3 pr-8">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected((int)$tahun === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Supplier</label>
                    <select wire:model.live="vendor_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-colors cursor-pointer py-2 pl-3 pr-8">
                        <option value="">— Semua Supplier —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected((string)$this->vendor_id === (string)$v->id)>
                                {{ $v->nama_vendor }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>


        </div>
    </div>


    {{-- ═══════════════════════ AREA CETAK ═══════════════════════ --}}
    <div id="print-area">

        {{-- Kop Laporan --}}
        <div class="text-center mb-5 print-only" style="display:none">
            <h2 style="font-weight:800;font-size:16px;text-transform:uppercase;letter-spacing:.05em;margin:0">CV Qutby Creativindo</h2>
            <h3 style="font-weight:700;font-size:14px;margin:4px 0 2px">LAPORAN PEMBELIAN BARANG</h3>
            <p style="font-size:12px;color:#555;margin:0">{{ $filterLabel }}</p>
        </div>
        <div class="text-center mb-4 no-print">
            <h2 class="font-extrabold text-gray-800 dark:text-white uppercase tracking-widest text-lg">CV Qutby Creativindo</h2>
            <h3 class="font-bold text-primary-700 dark:text-primary-400 text-base mt-1">LAPORAN PEMBELIAN BARANG</h3>
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-0.5">{{ $filterLabel }}</p>
        </div>

        {{-- Tabel --}}
        <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 overflow-hidden shadow-sm bg-white dark:bg-gray-900">

            @if($rows->isEmpty())
                <div class="py-24 text-center">
                    <div class="flex flex-col items-center gap-4">
                        <div class="p-5 bg-primary-50 dark:bg-gray-800 rounded-full shadow-sm">
                            <x-heroicon-o-document-chart-bar class="w-12 h-12 text-primary-400 dark:text-primary-500" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-700 dark:text-gray-200 text-lg">Belum Ada Data</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Tidak ada transaksi pembelian untuk filter: <strong>{{ $filterLabel }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse" id="tabel-laporan-pembelian">
                        <thead>
                            <tr style="background-color:#1d4ed8;color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:4%">No</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:9%">Tanggal</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:10%">No. PO</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:14%">Nama Supplier</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:9%">Kode Barang</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:16%">Nama Barang</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:5%">Qty</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:11%">Harga Satuan</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:12%">Total Biaya</th>
                                <th style="padding:10px 8px;text-align:center;border:1px solid #1e40af;font-weight:700;width:10%">Status Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $row)
                                @php
                                    $status   = strtolower($row['status'] ?? '');
                                    $isLunas  = in_array($status, ['lunas', 'paid']);
                                    $bgRow    = $i % 2 === 0 ? 'background:#fff' : 'background:#f9fafb';
                                @endphp
                                <tr style="{{ $bgRow }};border-bottom:1px solid #e5e7eb;" class="hover:bg-primary-50/30 transition-colors">

                                    {{-- No --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;color:#9ca3af;font-size:11px;font-family:monospace">
                                        {{ $i + 1 }}
                                    </td>

                                    {{-- Tanggal --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;color:#6b7280;font-size:11px;white-space:nowrap">
                                        {{ \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d') }}
                                    </td>

                                    {{-- No. PO --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;">
                                        <span class="font-mono text-xs font-bold text-primary-700 dark:text-primary-400" style="background:#eff6ff;padding:2px 6px;border-radius:4px;font-size:11px">
                                            {{ $row['nomor'] }}
                                        </span>
                                    </td>

                                    {{-- Nama Supplier --}}
                                    <td style="padding:9px 8px;border:1px solid #e5e7eb;font-size:12px;font-weight:500;color:#1f2937">
                                        {{ $row['nama_vendor'] }}
                                    </td>

                                    {{-- Kode Barang --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;">
                                        <span style="font-family:monospace;font-size:11px;background:#f3f4f6;padding:2px 6px;border-radius:4px;color:#374151">
                                            {{ $row['kode_barang'] }}
                                        </span>
                                    </td>

                                    {{-- Nama Barang --}}
                                    <td style="padding:9px 8px;border:1px solid #e5e7eb;font-size:12px;color:#374151">
                                        {{ $row['nama_barang'] }}
                                    </td>

                                    {{-- Qty --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;font-size:12px;font-weight:600;color:#111827">
                                        {{ $row['qty'] > 0 ? number_format($row['qty']) : '-' }}
                                    </td>

                                    {{-- Harga Satuan --}}
                                    <td style="padding:9px 12px;text-align:right;border:1px solid #e5e7eb;">
                                        @if($row['harga_satuan'] > 0)
                                            <span style="color:#9ca3af;font-size:10px;margin-right:2px">Rp</span>
                                            <span style="font-family:monospace;font-size:12px;color:#1f2937">{{ number_format($row['harga_satuan'], 0, ',', '.') }}</span>
                                        @else
                                            <span style="color:#d1d5db">-</span>
                                        @endif
                                    </td>

                                    {{-- Total Biaya --}}
                                    <td style="padding:9px 12px;text-align:right;border:1px solid #e5e7eb;">
                                        <span style="color:#9ca3af;font-size:10px;margin-right:2px">Rp</span>
                                        <span style="font-family:monospace;font-size:12px;font-weight:700;color:#111827">{{ number_format($row['total_biaya'], 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td style="padding:9px 8px;text-align:center;border:1px solid #e5e7eb;">
                                        @if($isLunas)
                                            <span style="display:inline-block;font-size:11px;font-weight:700;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:9999px;padding:2px 10px">
                                                Lunas
                                            </span>
                                        @else
                                            <span style="display:inline-block;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:9999px;padding:2px 10px">
                                                {{ ucfirst($status ?: 'Pending') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Grand Total --}}
                        <tfoot>
                            <tr style="background-color:#eff6ff;font-weight:700;border-top:2px solid #93c5fd;">
                                <td colspan="8" style="padding:11px 14px;text-align:right;border:1px solid #d1d5db;font-size:13px;color:#374151;text-transform:uppercase;letter-spacing:.05em;">
                                    TOTAL:
                                </td>
                                <td style="padding:11px 14px;text-align:right;border:1px solid #d1d5db;">
                                    <span style="color:#6b7280;font-size:11px;margin-right:2px">Rp</span>
                                    <span style="font-family:monospace;font-size:15px;font-weight:800;color:#1d4ed8">{{ number_format($grandTotal, 0, ',', '.') }}</span>
                                </td>
                                <td style="border:1px solid #d1d5db;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ CSS ═══════════ --}}
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area { position: fixed; top: 0; left: 0; width: 100%; padding: 16px; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            table { border-collapse: collapse !important; width: 100% !important; }
            th, td { border: 1px solid #333 !important; font-size: 10px !important; }
            thead tr { background-color: #1e40af !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tfoot tr { background-color: #eff6ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .print-only { display: none; }
    </style>

</x-filament-panels::page>
