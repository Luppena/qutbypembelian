<x-filament-panels::page>
    @php
        $cards = $this->getCards();
        $summary = $this->getSummary();
        $preview = $this->getPreview();
        $stockError = $this->getStockError();
        $hargaWarning = $this->getHargaWarning();
        $fmtQty = fn ($value) => (float) $value > 0 ? number_format((float) $value, 0, ',', '.') : '-';
        $fmtRp = fn ($value) => (float) $value > 0 ? 'Rp ' . number_format((float) $value, 0, ',', '.') : '-';
        $fmtNominal = fn ($value) => (float) $value > 0 ? number_format((float) $value, 0, ',', '.') : '-';
    @endphp

    <div style="display:flex; flex-direction:column; gap:18px;">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <div style="display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                    <div style="width: 200px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Bulan</label>
                        <select wire:model.live="bulan" style="width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-size:14px; padding:7px 10px; height:38px;">
                            @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="width: 160px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Tahun</label>
                        <select wire:model.live="tahun" style="width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-size:14px; padding:7px 10px; height:38px;">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div style="width: 280px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Barang</label>
                        <select wire:model.live="barangId" style="width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-size:14px; padding:7px 10px; height:38px;">
                            <option value="">Semua Barang</option>
                            @foreach($this->getBarangOptions() as $id => $namaBarang)
                                <option value="{{ $id }}">{{ $namaBarang }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            <div style="padding:20px;">
                <div style="margin-bottom:18px;">
                    <h2 style="font-size:16px; font-weight:800; color:#111827; margin:0 0 4px;">
                        Kartu Stok Average Perpetual - Periode: {{ $this->getPeriodeLabel() }}
                    </h2>
                    <p style="font-size:12px; color:#6b7280; margin:0;">Setiap barang ditampilkan dalam kartu terpisah. Persediaan menunjukkan saldo rata-rata aktif setelah transaksi.</p>
                </div>

                @if(empty($cards))
                    <div style="text-align:center; padding:60px 20px; color:#6b7280; font-size:14px; border:1px dashed #d1d5db; border-radius:8px; background:#f9fafb;">
                        Belum ada data barang masuk untuk filter ini.
                    </div>
                @else
                    <div style="display:flex; flex-direction:column; gap:28px;">
                        @foreach($cards as $card)
                            <section style="border:1px solid #d1d5db; border-radius:8px; overflow:hidden; background:#ffffff;">
                                <div style="padding:14px 16px; border-bottom:1px solid #d1d5db;">
                                    <h3 style="margin:0; color:#111827; font-size:15px; font-weight:800; text-transform:uppercase;">
                                        Kartu Stok Average {{ $card['barang']->nama_barang }}
                                    </h3>
                                    <div style="margin-top:4px; color:#6b7280; font-size:12px;">
                                        Kode: {{ $card['barang']->kode_barang ?? '-' }}
                                    </div>
                                </div>

                                <div style="overflow-x:auto;">
                                    <table style="width:100%; min-width:1120px; border-collapse:collapse; font-size:12px; color:#111827;">
                                        <thead>
                                            <tr style="background:#f3f4f6;">
                                                <th rowspan="2" style="border:1px solid #d1d5db; padding:8px; text-align:center;">Tanggal</th>
                                                <th rowspan="2" style="border:1px solid #d1d5db; padding:8px; text-align:left;">Keterangan</th>
                                                <th colspan="3" style="border:1px solid #d1d5db; padding:8px; text-align:center;">Pembelian</th>
                                                <th colspan="3" style="border:1px solid #d1d5db; padding:8px; text-align:center;">HPP</th>
                                                <th colspan="3" style="border:1px solid #d1d5db; padding:8px; text-align:center;">Persediaan</th>
                                            </tr>
                                            <tr style="background:#f9fafb;">
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:center;">Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Harga/Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Total</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:center;">Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Harga/Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Total</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:center;">Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Harga/Unit</th>
                                                <th style="border:1px solid #d1d5db; padding:7px; text-align:right;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($card['rows'] as $row)
                                                <tr>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:center; white-space:nowrap;">{{ $row['tanggal'] }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; min-width:180px;">{{ $row['keterangan'] }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:center;">{{ $fmtQty($row['pembelian']['qty'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">{{ $fmtNominal($row['pembelian']['harga'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">{{ $fmtNominal($row['pembelian']['total'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:center;">{{ $fmtQty($row['hpp']['qty'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">{{ $fmtNominal($row['hpp']['harga'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">{{ $fmtNominal($row['hpp']['total'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:center;">{{ $fmtQty($row['persediaan']['qty'] ?? 0) }}</td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">
                                                        {{ $fmtNominal($row['persediaan']['harga'] ?? 0) }}@if($row['persediaan']['average_changed'] ?? false) <strong style="color:#d97706;">*</strong>@endif
                                                    </td>
                                                    <td style="border:1px solid #d1d5db; padding:8px; text-align:right;">{{ $fmtNominal($row['persediaan']['total'] ?? 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr style="background:#f9fafb; font-weight:800;">
                                                <td colspan="2" style="border:1px solid #d1d5db; padding:9px; text-align:center;">Total</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:center;">{{ $fmtQty($card['total_pembelian_unit']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">-</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">{{ $fmtNominal($card['total_pembelian']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:center;">{{ $fmtQty($card['total_jual_unit']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">-</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">{{ $fmtNominal($card['total_hpp']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:center;">{{ $fmtQty($card['stok_akhir']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">{{ $fmtNominal($card['harga_rata_rata_akhir']) }}</td>
                                                <td style="border:1px solid #d1d5db; padding:9px; text-align:right;">{{ $fmtNominal($card['persediaan_akhir']) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div style="padding:12px 16px; display:flex; gap:16px; flex-wrap:wrap; border-top:1px solid #e5e7eb; background:#f9fafb; font-size:12px;">
                                    <span>Saldo Awal: <strong>{{ $fmtRp($card['saldo_awal_nilai']) }}</strong></span>
                                    <span>Total Pembelian: <strong>{{ $fmtRp($card['total_pembelian']) }}</strong></span>
                                    <span>Total HPP: <strong>{{ $fmtRp($card['total_hpp']) }}</strong></span>
                                    <span>Persediaan Akhir: <strong>{{ $fmtRp($card['persediaan_akhir']) }}</strong></span>
                                    <span style="font-weight:800;">Validasi: {{ $card['valid'] ? 'Sesuai' : 'Tidak sesuai' }}</span>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($showForm)
        <div style="position:fixed; inset:0; z-index:50; background:rgba(17,24,39,.45); display:flex; align-items:center; justify-content:center; padding:20px;">
            <div style="width:100%; max-width:620px; background:#fff; border-radius:8px; box-shadow:0 20px 60px rgba(15,23,42,.24); overflow:hidden;">
                <div style="padding:16px 18px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:16px; font-weight:800; color:#111827;">Tambah Transaksi Average</h3>
                    <button type="button" wire:click="closeForm" style="border:0; background:#f3f4f6; color:#374151; width:32px; height:32px; border-radius:6px; font-size:18px;">x</button>
                </div>

                <div style="padding:18px; display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div style="grid-column:1 / -1;">
                        <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Barang</label>
                        <select wire:model.live="formBarangId" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:9px 10px;">
                            <option value="">Pilih Barang</option>
                            @foreach($this->getBarangOptions() as $id => $namaBarang)
                                <option value="{{ $id }}">{{ $namaBarang }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Tanggal</label>
                        <input type="date" wire:model.live="formTanggal" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px;">
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Jenis</label>
                        <select wire:model.live="formJenis" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:9px 10px;">
                            <option value="beli">Pembelian</option>
                            <option value="jual">Penjualan</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Qty</label>
                        <input type="number" min="1" wire:model.live="formQty" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px;">
                    </div>

                    @if($formJenis === 'beli')
                        <div>
                            <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Harga Beli/unit</label>
                            <input type="number" min="0" step="0.01" wire:model.live="formHargaBeli" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px;">
                            @if($hargaWarning)
                                <div style="margin-top:6px; color:#b45309; font-size:12px; font-weight:700;">{{ $hargaWarning }}</div>
                            @endif
                        </div>
                    @endif

                    <div style="grid-column:1 / -1;">
                        <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Keterangan</label>
                        <input type="text" wire:model.live="formKeterangan" placeholder="No. PO / No. Faktur Jual" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:8px 10px;">
                    </div>

                    @if($stockError)
                        <div style="grid-column:1 / -1; padding:10px 12px; border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; border-radius:6px; font-size:13px; font-weight:700;">
                            {{ $stockError }}
                        </div>
                    @endif

                    @if($formBarangId !== '')
                        <div style="grid-column:1 / -1; border:1px solid #e5e7eb; background:#f9fafb; border-radius:8px; padding:12px; display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px;">
                            @if($formJenis === 'beli')
                                <div>Harga rata-rata saat ini: <strong>{{ $fmtRp($preview['harga_rata_rata_saat_ini'] ?? 0) }}</strong></div>
                                <div>Nilai masuk: <strong>{{ $fmtRp($preview['nilai_masuk'] ?? 0) }}</strong></div>
                                <div>★ Harga rata-rata baru: <strong>{{ $fmtRp($preview['harga_rata_rata_baru'] ?? 0) }}</strong></div>
                                <div>Stok setelah: <strong>{{ $fmtQty($preview['stok_setelah'] ?? 0) }} pcs</strong></div>
                            @else
                                <div>HPP per unit: <strong>{{ $fmtRp($preview['hpp_per_unit'] ?? 0) }}</strong></div>
                                <div>Total HPP: <strong>{{ $fmtRp($preview['total_hpp'] ?? 0) }}</strong></div>
                                <div>Stok setelah: <strong>{{ $fmtQty($preview['stok_setelah'] ?? 0) }} pcs</strong></div>
                            @endif
                        </div>
                    @endif
                </div>

                <div style="padding:14px 18px; border-top:1px solid #e5e7eb; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" wire:click="closeForm" style="height:38px; padding:0 14px; border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:6px; font-weight:700;">Batal</button>
                    <button type="button" wire:click="saveTransaksi" @disabled(! $this->canSave()) style="height:38px; padding:0 16px; border:1px solid #b45309; background:{{ $this->canSave() ? '#d97706' : '#d1d5db' }}; color:#fff; border-radius:6px; font-weight:800;">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
