<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl border border-gray-200 dark:border-gray-700">

        {{-- FILTER PANEL --}}
        <div class="p-5 border-b border-gray-200 dark:border-gray-700">
            <div style="display:flex; align-items:flex-end; gap:1rem;">
                <div style="width: 200px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Bulan</label>
                    <select wire:model.live="bulan" style="width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-size:14px; padding:7px 10px; cursor:pointer; height: 38px;">
                        @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                                   '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $v => $l)
                            <option value="{{ $v }}" @selected($bulan === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="width: 160px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Tahun</label>
                    <select wire:model.live="tahun" style="width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-size:14px; padding:7px 10px; cursor:pointer; height: 38px;">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected((int)$tahun === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <a href="{{ route('kartu-stok.pdf', ['bulan' => $bulan, 'tahun' => $tahun]) }}" target="_blank" style="display:inline-flex; align-items:center; justify-content:center; background-color: #3b82f6; color: #ffffff; padding: 0 16px; border-radius: 6px; font-size: 14px; font-weight: 500; text-decoration: none; height: 38px; border:1px solid #2563eb; transition: background-color 0.2s; cursor: pointer;">
                        <svg style="width: 16px; height: 16px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- TABEL --}}
        @php
            $data = $this->getLaporanData();
            $totSaldoAwalQty   = collect($data)->sum('saldo_awal_qty');
            $totSaldoAwalNilai = collect($data)->sum('saldo_awal_nilai');
            $totMasukQty       = collect($data)->sum('masuk_qty');
            $totMasukNilai     = collect($data)->sum('masuk_nilai');
            $totKeluarQty      = collect($data)->sum('keluar_qty');
            $totKeluarNilai    = collect($data)->sum('keluar_nilai');
            $totAkhirQty       = collect($data)->sum('saldo_akhir_qty');
            $totAkhirNilai     = collect($data)->sum('saldo_akhir_nilai');
        @endphp

        <div style="padding:20px; overflow-x:auto;">

            <div style="margin-bottom:16px;">
                <h2 style="font-size:16px; font-weight:700; color:#111827; margin:0 0 4px;">
                    Rekap Kartu Stok &mdash; Periode: {{ $this->getPeriodeLabel() }}
                </h2>
                <p style="font-size:12px; color:#9ca3af; margin:0;">Semua barang dengan saldo / mutasi di bulan ini</p>
            </div>

            @if(empty($data))
                <div style="text-align:center; padding:60px 0; color:#9ca3af; font-size:14px;">
                    Tidak ada barang dengan saldo atau mutasi di periode ini.
                </div>
            @else
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th rowspan="2" style="border:1px solid #d1d5db; padding:8px 10px; text-align:center; vertical-align:middle; color:#374151; font-weight:600; white-space:nowrap;">No</th>
                            <th rowspan="2" style="border:1px solid #d1d5db; padding:8px 10px; text-align:center; vertical-align:middle; color:#374151; font-weight:600; white-space:nowrap;">Kode</th>
                            <th rowspan="2" style="border:1px solid #d1d5db; padding:8px 14px; text-align:left;   vertical-align:middle; color:#374151; font-weight:600;">Nama Barang</th>
                            <th colspan="2" style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#374151; font-weight:600;">Saldo Awal</th>
                            <th colspan="2" style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#374151; font-weight:600;">Masuk</th>
                            <th colspan="2" style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#374151; font-weight:600;">Keluar (HPP)</th>
                            <th colspan="2" style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#374151; font-weight:600;">Saldo Akhir</th>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#6b7280; font-weight:500; font-size:12px;">Qty</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:right;  color:#6b7280; font-weight:500; font-size:12px;">Nilai (Rp)</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#6b7280; font-weight:500; font-size:12px;">Qty</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:right;  color:#6b7280; font-weight:500; font-size:12px;">Nilai (Rp)</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#6b7280; font-weight:500; font-size:12px;">Qty</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:right;  color:#6b7280; font-weight:500; font-size:12px;">Nilai (Rp)</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:center; color:#6b7280; font-weight:500; font-size:12px;">Qty</th>
                            <th style="border:1px solid #d1d5db; padding:6px 10px; text-align:right;  color:#6b7280; font-weight:500; font-size:12px;">Nilai (Rp)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($data as $i => $row)
                            @php $bg = $i % 2 === 0 ? '#ffffff' : '#f9fafb'; @endphp
                            <tr style="background:{{ $bg }};">
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; color:#9ca3af; font-size:12px;">{{ $i + 1 }}</td>
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; font-family:monospace; font-size:12px; font-weight:600; color:#374151;">{{ $row['barang']->kode_barang }}</td>
                                <td style="border:1px solid #e5e7eb; padding:8px 14px; font-weight:500; color:#111827;">{{ $row['barang']->nama_barang }}</td>

                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; color:#374151; font-weight:600;">
                                    {{ $row['saldo_awal_qty'] > 0 ? number_format($row['saldo_awal_qty']) : '-' }}
                                </td>
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:right; color:#374151;">
                                    {{ $row['saldo_awal_nilai'] > 0 ? number_format($row['saldo_awal_nilai'], 0, ',', '.') : '-' }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; color:#374151; font-weight:600;">
                                    {{ $row['masuk_qty'] > 0 ? number_format($row['masuk_qty']) : '-' }}
                                </td>
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:right; color:#374151;">
                                    {{ $row['masuk_nilai'] > 0 ? number_format($row['masuk_nilai'], 0, ',', '.') : '-' }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; color:#374151; font-weight:600;">
                                    {{ $row['keluar_qty'] > 0 ? number_format($row['keluar_qty']) : '-' }}
                                </td>
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:right; color:#374151;">
                                    {{ $row['keluar_nilai'] > 0 ? number_format($row['keluar_nilai'], 0, ',', '.') : '-' }}
                                </td>

                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:center; color:#111827; font-weight:700;">
                                    {{ number_format($row['saldo_akhir_qty']) }}
                                </td>
                                <td style="border:1px solid #e5e7eb; padding:8px 10px; text-align:right; color:#111827; font-weight:600;">
                                    {{ number_format($row['saldo_akhir_nilai'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr style="background:#f3f4f6; font-weight:700; font-size:13px;">
                            <td colspan="3" style="border:1px solid #d1d5db; padding:10px 14px; text-align:center; color:#374151; text-transform:uppercase; font-size:11px; letter-spacing:.05em;">Total</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:center; color:#111827;">{{ number_format($totSaldoAwalQty) }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:right;  color:#111827;">{{ number_format($totSaldoAwalNilai, 0, ',', '.') }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:center; color:#111827;">{{ number_format($totMasukQty) }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:right;  color:#111827;">{{ number_format($totMasukNilai, 0, ',', '.') }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:center; color:#111827;">{{ number_format($totKeluarQty) }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:right;  color:#111827;">{{ number_format($totKeluarNilai, 0, ',', '.') }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:center; color:#111827;">{{ number_format($totAkhirQty) }}</td>
                            <td style="border:1px solid #d1d5db; padding:10px; text-align:right;  color:#111827;">{{ number_format($totAkhirNilai, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                <p style="font-size:11px; color:#9ca3af; margin-top:10px;">
                    * Nilai menggunakan metode FIFO. Saldo Akhir = Saldo Awal + Masuk &minus; Keluar.
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
