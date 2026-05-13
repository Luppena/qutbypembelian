@php
    use App\Filament\Resources\GrnResource;

    $po = $this->getRecord()->loadMissing(['vendor', 'details.barang', 'details.grnDetails.grn', 'grns']);
    $lihatGrnUrl = GrnResource::getUrl('index') . '?pembelian_id=' . $po->id;
    $formatRupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggalPenerimaan = $po->grns
        ->filter(fn ($grn) => filled($grn->tanggal_terima))
        ->sortByDesc('tanggal_terima')
        ->first()?->tanggal_terima;
    $estimasiDatang = $tanggalPenerimaan ?? $po->estimasi_datang;

    $statusPengirimanState = match ($po->status) {
        'partial' => 'sebagian',
        'selesai' => 'selesai',
        default => $po->status_pengiriman ?: 'dalam_kirim',
    };

    $statusPengiriman = match ($statusPengirimanState) {
        'dalam_kirim' => 'Dalam Pengiriman',
        'sebagian' => 'Sebagian Diterima',
        'selesai' => 'Selesai',
        default => ucfirst(str_replace('_', ' ', (string) $statusPengirimanState)),
    };

    $statusPo = match ($po->status) {
        'partial' => 'Partial',
        'selesai' => 'Selesai',
        'menunggu' => 'Menunggu',
        default => ucfirst((string) $po->status),
    };

    $totalItem = $po->details->count();
    $sudahLengkap = $po->details->filter(fn ($detail) => (int) $detail->qty_outstanding === 0)->count();
    $masihKurang = $totalItem - $sudahLengkap;
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    :root {
        --color-background-primary: #ffffff;
        --color-background-secondary: #f8fafc;
        --color-background-tertiary: #f4f7fb;
        --color-border-secondary: #cbd5e1;
        --color-border-tertiary: #e2e8f0;
        --color-text-primary: #0f172a;
        --color-text-secondary: #475569;
        --color-text-tertiary: #8090a5;
        --border-radius-md: 6px;
        --border-radius-lg: 10px;
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --color-background-primary: #111827;
            --color-background-secondary: #172033;
            --color-background-tertiary: #0f172a;
            --color-border-secondary: #475569;
            --color-border-tertiary: #334155;
            --color-text-primary: #f8fafc;
            --color-text-secondary: #cbd5e1;
            --color-text-tertiary: #94a3b8;
        }
    }

    html,
    body {
        overflow-x: hidden;
    }

    .q-po-widget,
    .q-po-widget * {
        box-sizing: border-box;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-weight: 400;
    }

    .q-po-widget {
        position: fixed;
        inset: 0;
        z-index: 60;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
        color: var(--color-text-primary);
        background: var(--color-background-tertiary);
    }

    .q-topbar {
        position: fixed;
        inset: 0 0 auto 0;
        z-index: 30;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--color-background-primary);
        border-bottom: 0.5px solid var(--color-border-tertiary);
    }

    .q-brand {
        padding-left: 28px;
        font-size: 20px;
        font-weight: 500;
        color: var(--color-text-primary);
        letter-spacing: 0;
    }

    .q-search {
        width: 236px;
        height: 38px;
        margin-right: 32px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 0.5px solid var(--color-border-tertiary);
        border-radius: var(--border-radius-md);
        background: var(--color-background-primary);
        color: var(--color-text-tertiary);
        font-size: 14px;
    }

    .q-search i {
        font-size: 18px;
    }

    .q-sidebar {
        position: fixed;
        top: 68px;
        left: 0;
        width: 312px;
        height: calc(100vh - 68px);
        overflow-y: auto;
        background: var(--color-background-primary);
        border-right: 0.5px solid var(--color-border-tertiary);
    }

    .q-nav {
        padding: 34px 16px;
    }

    .q-section-label {
        padding: 24px 34px 14px;
        color: var(--color-text-tertiary);
        font-size: 11px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .q-nav-item {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 4px 0;
        padding: 11px 10px;
        border-radius: 8px;
        color: var(--color-text-secondary);
        text-decoration: none;
        font-size: 15px;
        line-height: 1.35;
    }

    .q-nav-item:hover {
        background: var(--color-background-secondary);
    }

    .q-nav-item i {
        width: 22px;
        font-size: 22px;
        color: currentColor;
    }

    .q-nav-item.is-active {
        background: #FEF3E2;
        color: #854F0B;
        font-weight: 500;
    }

    .q-nav-badge {
        margin-left: auto;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #F59E0B;
        color: #ffffff;
        font-size: 12px;
        font-weight: 500;
    }

    .q-main {
        position: fixed;
        left: 312px;
        top: 68px;
        right: 0;
        bottom: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 32px;
        background: var(--color-background-tertiary);
    }

    .q-breadcrumb {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--color-text-tertiary);
        font-size: 12px;
    }

    .q-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 28px;
    }

    .q-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .q-back-btn {
        width: 42px;
        height: 42px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0.5px solid var(--color-border-tertiary);
        border-radius: 999px;
        background: var(--color-background-primary);
        color: var(--color-text-secondary);
        text-decoration: none;
    }

    .q-back-btn i {
        font-size: 24px;
    }

    .q-title {
        margin: 0;
        color: var(--color-text-primary);
        font-size: 30px;
        line-height: 1.25;
        font-weight: 500;
        letter-spacing: 0;
    }

    .q-primary-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: var(--border-radius-md);
        background: #185FA5;
        color: #ffffff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        border: 0.5px solid #185FA5;
    }

    .q-card {
        width: 100%;
        margin-bottom: 30px;
        padding: 0;
        border: 0.5px solid var(--color-border-tertiary);
        border-radius: var(--border-radius-lg);
        background: var(--color-background-primary);
    }

    .q-card-title {
        margin-bottom: 0;
        padding: 18px 24px;
        border-bottom: 0.5px solid var(--color-border-tertiary);
        color: var(--color-text-primary);
        font-size: 16px;
        line-height: 1.25;
        font-weight: 500;
    }

    .q-card-title.compact {
        margin-bottom: 0;
    }

    .q-info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        padding: 24px;
    }

    .q-field {
        min-width: 0;
    }

    .q-label {
        margin-bottom: 8px;
        color: var(--color-text-tertiary);
        font-size: 11px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .q-readonly {
        width: 100%;
        min-height: 38px;
        display: flex;
        align-items: center;
        border: 0.5px solid var(--color-border-tertiary);
        border-radius: var(--border-radius-md);
        padding: 8px 12px;
        background: var(--color-background-primary);
        color: var(--color-text-primary);
        font-size: 13px;
        cursor: default;
    }

    .q-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 12px;
        line-height: 1;
        font-weight: 500;
    }

    .q-badge-sm {
        padding: 2px 8px;
        font-size: 11px;
        margin-left: 6px;
    }

    .q-badge-orange {
        background: #FAEEDA;
        color: #633806;
    }

    .q-badge-green {
        background: #EAF3DE;
        color: #27500A;
    }

    .q-badge-blue {
        background: #E6F1FB;
        color: #0C447C;
    }

    .q-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 13px;
    }

    .q-table-wrap {
        padding: 24px 24px 0;
    }

    .q-table th {
        padding: 0 0 10px;
        border-bottom: 0.5px solid var(--color-border-tertiary);
        color: var(--color-text-tertiary);
        font-size: 11px;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        font-weight: 400;
    }

    .q-table td {
        padding: 12px 0;
        border-bottom: 0.5px solid var(--color-border-tertiary);
        color: var(--color-text-primary);
        vertical-align: middle;
    }

    .q-table tr:last-child td {
        border-bottom: 0;
    }

    .q-item-name {
        display: block;
        color: var(--color-text-primary);
        font-weight: 500;
    }

    .q-item-code {
        display: block;
        margin-top: 2px;
        color: var(--color-text-tertiary);
        font-size: 11px;
    }

    .q-orange-text {
        color: #854F0B;
    }

    .q-green-text {
        color: #3B6D11;
    }

    .q-muted-text {
        color: var(--color-text-tertiary);
    }

    .q-summary {
        margin: 12px 24px 24px;
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 12px 16px;
        border-radius: var(--border-radius-md);
        background: var(--color-background-secondary);
        color: var(--color-text-secondary);
        font-size: 13px;
    }

    .q-summary strong {
        font-weight: 500;
    }

    @media (max-width: 900px) {
        .q-main {
            left: 0;
            padding: 20px;
        }

        .q-sidebar {
            display: none;
        }

        .q-info-grid {
            grid-template-columns: 1fr;
        }

        .q-summary {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
        }

        .q-table {
            min-width: 760px;
        }

        .q-table-wrap {
            overflow-x: auto;
        }
    }
</style>

<div class="q-po-widget">
    <header class="q-topbar">
        <div class="q-brand">CV QUTBY CREATIVINDO</div>
        <div class="q-search">
            <i class="ti ti-search"></i>
            <span>Cari</span>
        </div>
    </header>

    <aside class="q-sidebar">
        <nav class="q-nav">
            <div class="q-section-label">Master Data</div>
            <a class="q-nav-item" href="#"><i class="ti ti-box"></i><span>Barang</span></a>
            <a class="q-nav-item" href="#"><i class="ti ti-building-store"></i><span>Vendor</span></a>

            <div class="q-section-label">Laporan</div>
            <a class="q-nav-item" href="#"><i class="ti ti-chart-bar"></i><span>Laporan Pembelian</span></a>
            <a class="q-nav-item" href="#"><i class="ti ti-list-details"></i><span>Kartu Stok FIFO</span></a>

            <div class="q-section-label">Transaksi Pembelian</div>
            <a class="q-nav-item is-active" href="#"><i class="ti ti-truck-delivery"></i><span>Barang Masuk</span><span class="q-nav-badge">2</span></a>
            <a class="q-nav-item" href="#"><i class="ti ti-shopping-cart"></i><span>Pesanan Pembelian</span></a>
            <a class="q-nav-item" href="#"><i class="ti ti-clipboard-check"></i><span>Penerimaan Barang</span></a>
            <a class="q-nav-item" href="#"><i class="ti ti-arrow-back-up"></i><span>Retur Pembelian</span></a>
        </nav>
    </aside>

    <main class="q-main">
        <div class="q-breadcrumb">
            <span>Daftar Barang Masuk (PO)</span>
            <span>→</span>
            <span>Lihat</span>
        </div>

        <div class="q-header-row">
            <div class="q-title-wrap">
                <a class="q-back-btn" href="{{ url()->previous() }}" aria-label="Kembali">
                    <i class="ti ti-chevron-left"></i>
                </a>
                <h1 class="q-title">Detail PO: {{ $po->nomor }}</h1>
            </div>
            <a class="q-primary-btn" href="{{ $lihatGrnUrl }}">
                <i class="ti ti-eye"></i>
                <span>Lihat GRN</span>
            </a>
        </div>

        <section class="q-card">
            <div class="q-card-title">Informasi Purchase Order</div>
            <div class="q-info-grid">
                <div class="q-field">
                    <div class="q-label">Nomor PO</div>
                    <div class="q-readonly">{{ $po->nomor }}</div>
                </div>
                <div class="q-field">
                    <div class="q-label">Tanggal PO</div>
                    <div class="q-readonly">{{ $po->tanggal?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div class="q-field">
                    <div class="q-label">Status Pengiriman</div>
                    <div class="q-readonly">
                        {{ $statusPengiriman }}
                    </div>
                </div>
                <div class="q-field">
                    <div class="q-label">Vendor</div>
                    <div class="q-readonly">{{ $po->vendor?->nama_vendor ?? '-' }}</div>
                </div>
                <div class="q-field">
                    <div class="q-label">Estimasi Datang</div>
                    <div class="q-readonly">{{ $estimasiDatang?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div class="q-field">
                    <div class="q-label">Status PO</div>
                    <div class="q-readonly">
                        {{ $statusPo }}
                    </div>
                </div>
            </div>
        </section>

        <section class="q-card">
            <div class="q-card-title compact">Detail Item PO</div>
            <div class="q-table-wrap">
                <table class="q-table">
                    <colgroup>
                        <col style="width: 30%">
                        <col style="width: 10%">
                        <col style="width: 16%">
                        <col style="width: 12%">
                        <col style="width: 16%">
                        <col style="width: 16%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Qty PO</th>
                            <th>Qty Diterima</th>
                            <th>Outstanding</th>
                            <th>Harga/Unit</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($po->details as $detail)
                            @php
                                $qtyPo = (int) $detail->qty;
                                $qtyDiterima = (int) $detail->qty_diterima;
                                $outstanding = max(0, $qtyPo - $qtyDiterima);
                                $isLengkap = $outstanding === 0 && $qtyDiterima > 0;
                                $subtotal = $qtyPo * (float) $detail->harga;
                            @endphp
                            <tr>
                                <td>
                                    <span class="q-item-name">{{ $detail->barang?->nama_barang ?? '-' }}</span>
                                    <span class="q-item-code">{{ $detail->barang?->kode_barang ?? 'BRG-' . str_pad((string) $detail->barang_id, 3, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>{{ number_format($qtyPo, 0, ',', '.') }}</td>
                                <td>
                                    <span class="{{ $isLengkap ? 'q-green-text' : 'q-orange-text' }}">{{ number_format($qtyDiterima, 0, ',', '.') }}</span>
                                    <span class="q-badge q-badge-sm {{ $isLengkap ? 'q-badge-green' : 'q-badge-orange' }}">{{ $isLengkap ? 'Lengkap' : 'Kurang' }}</span>
                                </td>
                                <td class="{{ $outstanding > 0 ? 'q-orange-text' : 'q-muted-text' }}">{{ number_format($outstanding, 0, ',', '.') }}</td>
                                <td>{{ $formatRupiah($detail->harga) }}</td>
                                <td>{{ $formatRupiah($subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="q-summary">
                <div>Total Item: <strong>{{ $totalItem }}</strong></div>
                <div>Sudah Lengkap: <strong class="q-green-text">{{ $sudahLengkap }} item</strong></div>
                <div>Masih Kurang: <strong class="q-orange-text">{{ $masihKurang }} item</strong></div>
            </div>
        </section>
    </main>
</div>
