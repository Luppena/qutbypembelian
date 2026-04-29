<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-700/80 rounded-xl shadow-sm p-4">

        <div class="flex items-end gap-3 flex-wrap">
            {{-- Bulan --}}
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Bulan</label>
                <select wire:model.live="bulan"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-8">
                    @foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                               '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $v => $l)
                        <option value="{{ $v }}" @selected($bulan === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tahun --}}
            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tahun</label>
                <select wire:model.live="tahun"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-8">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" @selected((int)$tahun === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Vendor --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Vendor</label>
                <select wire:model.live="vendor_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-8">
                    <option value="">— Semua Vendor —</option>
                    @foreach($this->getVendorOptions() as $id => $nama)
                        <option value="{{ $id }}" @selected((string)$vendor_id === (string)$id)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</label>
                <select wire:model.live="status"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-8">
                    <option value="">— Semua Status —</option>
                    <option value="lunas" @selected($status === 'lunas')>Lunas</option>
                    <option value="belum_lunas" @selected($status === 'belum_lunas')>Belum Lunas</option>
                </select>
            </div>

            {{-- Tombol Cetak PDF --}}
            <div>
                <button wire:click="cetakPdf" type="button"
                    style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background-color:#dc2626; color:#fff; font-size:14px; font-weight:600; border-radius:8px; border:none; cursor:pointer; white-space:nowrap;"
                    onmouseover="this.style.backgroundColor='#b91c1c'"
                    onmouseout="this.style.backgroundColor='#dc2626'">
                    <svg style="width:16px;height:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Cetak PDF
                </button>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
