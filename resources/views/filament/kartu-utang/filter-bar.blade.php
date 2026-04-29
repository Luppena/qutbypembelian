<div class="p-4 bg-white rounded-xl shadow-sm border">
    <div class="grid grid-cols-12 gap-4 items-end">
        <div class="col-span-12 md:col-span-4">
            <label class="text-sm font-medium">Vendor</label>
            <select wire:model.live="tableFilters.vendor_id.value" class="w-full rounded-lg border-gray-300">
                <option value="">Semua Vendor</option>
                @foreach(\App\Models\Vendor::orderBy('nama_vendor')->get() as $v)
                    <option value="{{ $v->id }}">{{ $v->nama_vendor }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-span-12 md:col-span-3">
            <label class="text-sm font-medium">Dari Tanggal</label>
            <input type="date" wire:model.live="tableFilters.periode.from" class="w-full rounded-lg border-gray-300">
        </div>

        <div class="col-span-12 md:col-span-3">
            <label class="text-sm font-medium">Sampai Tanggal</label>
            <input type="date" wire:model.live="tableFilters.periode.until" class="w-full rounded-lg border-gray-300">
        </div>

        <div class="col-span-12 md:col-span-2 flex gap-2">
            <button wire:click="$refresh" class="w-full px-4 py-2 rounded-lg bg-red-700 text-white">
                Cari
            </button>
            <button wire:click="resetFilterBar" class="w-full px-4 py-2 rounded-lg border">
                Reset
            </button>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        <button wire:click="setPeriode('bulan_ini')" class="px-3 py-1 rounded-lg bg-amber-400">
            Bulan Ini
        </button>
        <button wire:click="setPeriode('bulan_lalu')" class="px-3 py-1 rounded-lg bg-amber-400">
            Bulan Lalu
        </button>
        <button wire:click="setPeriode('tahun_ini')" class="px-3 py-1 rounded-lg bg-amber-400">
            Tahun Ini
        </button>
    </div>
</div>