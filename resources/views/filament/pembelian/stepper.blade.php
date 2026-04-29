@php
    /** @var \App\Models\Pembelian $record */

    $hasReceipt  = (bool) $record->penerimaanBarang;
    $hasInvoice  = (bool) $record->fakturPembelian;
    $hasPayment  = $hasInvoice && ($record->fakturPembelian->pembayarans?->isNotEmpty() ?? false);

    // Tentukan current step (1-4)
    $current = 1;
    if ($hasReceipt) $current = 2;
    if ($hasInvoice) $current = 3;
    if ($hasPayment) $current = 4;

    $steps = [
        1 => ['title' => 'Pembelian',   'desc' => 'Pesanan dibuat'],
        2 => ['title' => 'Penerimaan',  'desc' => 'Barang diterima'],
        3 => ['title' => 'Faktur',      'desc' => 'Faktur dibuat'],
        4 => ['title' => 'Pembayaran',  'desc' => 'Pembayaran lunas'],
    ];
@endphp

<div class="rounded-2xl border bg-white p-6 shadow-sm">
    <div class="mb-4">
        <div class="text-lg font-semibold">Proses Pembelian</div>
        <div class="text-sm text-gray-500">Alur: Pembelian → Penerimaan → Faktur → Pembayaran</div>
    </div>

    {{-- Line + Dots --}}
    <div class="relative mb-6">
        <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-gray-200 rounded"></div>

        @php
            // progress width (0..100)
            $progress = match ($current) {
                1 => 0,
                2 => 33,
                3 => 66,
                4 => 100,
                default => 0,
            };
        @endphp

        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-600 rounded" style="width: {{ $progress }}%"></div>

        <div class="grid grid-cols-4 gap-4 relative">
            @foreach($steps as $i => $s)
                @php
                    $done = $i < $current || ($i === 4 && $hasPayment);
                    $active = $i === $current;
                @endphp

                <div class="flex items-center justify-center">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center
                        {{ $done ? 'bg-indigo-600 text-white' : ($active ? 'bg-white border-2 border-indigo-600 text-indigo-600' : 'bg-white border border-gray-300 text-gray-400') }}
                    ">
                        @if($done)
                            {{-- check icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-sm font-semibold">{{ str_pad((string)$i, 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($steps as $i => $s)
            @php
                $done = $i < $current || ($i === 4 && $hasPayment);
                $active = $i === $current;
            @endphp

            <div class="rounded-xl border p-4
                {{ $done ? 'border-indigo-200 bg-indigo-50' : ($active ? 'border-indigo-600 bg-white' : 'border-gray-200 bg-white') }}
            ">
                <div class="text-xs text-gray-500 mb-1">STEP {{ $i }}</div>
                <div class="font-semibold">{{ $s['title'] }}</div>
                <div class="text-sm text-gray-500">{{ $s['desc'] }}</div>

                <div class="mt-3 text-xs">
                    @if($done)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-green-700">Selesai</span>
                    @elseif($active)
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-yellow-700">Sedang Diproses</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-gray-600">Menunggu</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>