<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan</label>
                <select wire:model.live="bulan"
                    class="fi-select-input block w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $k => $v)
                        <option value="{{ $k }}" @selected($bulan === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun</label>
                <select wire:model.live="tahun"
                    class="fi-select-input block w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    @for($y = now()->year; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" @selected($tahun == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                <select wire:model.live="vendor_id"
                    class="fi-select-input block w-48 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    <option value="">-- Semua Vendor --</option>
                    @foreach($this->getVendorOptions() as $v)
                        <option value="{{ $v->id }}" @selected($vendor_id == $v->id)>{{ $v->nama_vendor }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ml-auto flex gap-2">
                <a href="{{ $this->getPdfUrl() }}" target="_blank"
                    class="fi-btn inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                    Cetak PDF
                </a>
                <a href="{{ $this->getExcelUrl() }}"
                    class="fi-btn inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10">
                    Export Excel
                </a>
            </div>
        </div>
    </div>

    @php
        $rows = $this->getRekapRows();
        $grandTotal = $rows->sum('total');
    @endphp

    <div class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
        Periode: {{ $this->getPeriodeLabel() }}
    </div>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-white dark:bg-gray-900">
                        <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 dark:border-gray-700 dark:text-white">Tanggal Pembelian</th>
                        <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 dark:border-gray-700 dark:text-white">Nama Barang</th>
                        <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 dark:border-gray-700 dark:text-white">Jumlah</th>
                        <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 dark:border-gray-700 dark:text-white">Harga Satuan</th>
                        <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 dark:border-gray-700 dark:text-white">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="border border-gray-300 px-4 py-3 text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                {{ $row['tanggal'] instanceof \Carbon\Carbon ? $row['tanggal']->format('d/m/Y') : \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-gray-700 dark:border-gray-700 dark:text-gray-300">{{ $row['nama_barang'] }}</td>
                            <td class="border border-gray-300 px-4 py-3 text-right text-gray-700 dark:border-gray-700 dark:text-gray-300">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                            <td class="border border-gray-300 px-4 py-3 text-right text-gray-700 dark:border-gray-700 dark:text-gray-300">Rp {{ number_format($row['harga_satuan'], 0, ',', '.') }}</td>
                            <td class="border border-gray-300 px-4 py-3 text-right text-gray-700 dark:border-gray-700 dark:text-gray-300">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-gray-300 px-4 py-10 text-center text-gray-400 dark:border-gray-700 dark:text-gray-500">
                                Tidak ada data pembelian yang sudah diterima dan lunas pada periode {{ $this->getPeriodeLabel() }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot>
                        <tr class="bg-white dark:bg-gray-900">
                            <td colspan="4" class="border border-gray-300 px-4 py-3 font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                                Total Pembelian Bulanan:
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-right font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
