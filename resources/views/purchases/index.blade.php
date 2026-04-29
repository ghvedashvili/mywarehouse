@extends('layouts.master')
@section('page_title')<i class="fa fa-cart-shopping me-2" style="color:#2980b9;"></i>შესყიდვები@endsection

@php
    use App\Models\Product_Order;
    $purchaseInTransit   = Product_Order::where('order_type','purchase')->whereNull('original_sale_id')->where('status_id',2)->count();
    $purchaseInWarehouse = Product_Order::where('order_type','purchase')->whereNull('original_sale_id')->where('status_id',3)->count();
    $purchaseTotal       = Product_Order::where('order_type','purchase')->whereNull('original_sale_id')->count();
    $returnsInTransit    = Product_Order::where('order_type','purchase')->whereNotNull('original_sale_id')->where('status_id',2)->count();
    $returnsTotal        = Product_Order::where('order_type','purchase')->whereNotNull('original_sale_id')->count();
@endphp

@section('top')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
/* ═══════════════════════ PURCHASE PAGE ═══════════════════════ */
*, *::before, *::after { box-sizing: border-box; }

.pu-page {
  --c-bg:            #eef0f5;
  --c-surface:       #ffffff;
  --c-surface2:      #f6f7fb;
  --c-border:        rgba(99,115,150,.12);
  --c-border-md:     rgba(99,115,150,.20);
  --c-border-strong: rgba(99,115,150,.32);
  --c-text-1:        #0d1117;
  --c-text-2:        #3d4a5c;
  --c-text-3:        #8892a4;
  --c-blue:          #2563eb;
  --c-blue-dim:      #eff6ff;
  --c-green:         #059669;
  --c-green-dim:     #ecfdf5;
  --c-red:           #dc2626;
  --c-red-dim:       #fef2f2;
  --c-amber:         #d97706;
  --c-amber-dim:     #fffbeb;
  --c-purple:        #7c3aed;
  --c-purple-dim:    #f5f3ff;
  --c-teal:          #0891b2;
  --c-teal-dim:      #ecfeff;
  --r-sm:   8px;
  --r-md:   12px;
  --r-lg:   16px;
  --r-pill: 999px;
  --sh-xs:  0 1px 2px rgba(0,0,0,.04);
  --sh-sm:  0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03);
  --sh-md:  0 4px 20px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
  --sh-lg:  0 8px 32px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.05);
  --sh-focus: 0 0 0 3px rgba(37,99,235,.18);
  --t-fast: .12s cubic-bezier(.4,0,.2,1);
  --t-base: .18s cubic-bezier(.4,0,.2,1);
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
  font-size: 14px;
  color: var(--c-text-1);
}

/* ── DataTables expand control ── */
table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control::before {
  background-color: var(--c-blue) !important;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(37,99,235,.35);
}

/* ── STATS ────────────────────────────────────────────────────── */
.pu-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}
@media (max-width: 900px) { .pu-stats { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 480px) { .pu-stats { grid-template-columns: repeat(2,1fr); gap:8px; } }

.pu-stat {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  padding: 14px 16px;
  box-shadow: var(--sh-sm);
  transition: box-shadow var(--t-base), transform var(--t-base);
  position: relative; overflow: hidden;
  cursor: default;
}
.pu-stat::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 2px;
  background: var(--stat-line, var(--c-blue));
  transform: scaleX(0); transform-origin: left;
  transition: transform .3s ease;
}
.pu-stat:hover { box-shadow: var(--sh-md); transform: translateY(-2px); }
.pu-stat:hover::after { transform: scaleX(1); }
.pu-stat-icon {
  width: 32px; height: 32px;
  border-radius: var(--r-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; margin-bottom: 10px;
}
.pu-stat-label {
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .7px;
  color: var(--c-text-3); margin-bottom: 4px;
}
.pu-stat-value {
  font-family: 'Outfit', sans-serif;
  font-size: 20px; font-weight: 700;
  letter-spacing: -.5px; color: var(--c-text-1); line-height: 1.1;
}
.pu-stat-sub { font-size: 10px; color: var(--c-text-3); margin-top: 3px; }

/* ── FILTER BAR ───────────────────────────────────────────────── */
.pu-filter-bar {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  padding: 10px 12px;
  display: flex; align-items: center; flex-wrap: wrap; gap: 7px;
  box-shadow: var(--sh-sm);
  margin-bottom: 12px;
}
.pu-pill-group { display: flex; gap: 3px; flex-wrap: wrap; }
.pu-pill {
  padding: 5px 11px;
  border-radius: var(--r-pill);
  font-size: 11.5px; font-weight: 600;
  border: 1px solid var(--c-border-md);
  background: transparent;
  color: var(--c-text-3);
  cursor: pointer;
  transition: all var(--t-fast);
  white-space: nowrap;
  font-family: inherit;
}
.pu-pill:hover { border-color: var(--c-amber); color: var(--c-amber); background: var(--c-amber-dim); }
.pu-pill.active-amber { background: var(--c-amber); border-color: var(--c-amber); color: #fff; box-shadow: 0 2px 8px rgba(217,119,6,.25); }
.pu-pill.active-green { background: var(--c-green); border-color: var(--c-green); color: #fff; box-shadow: 0 2px 8px rgba(5,150,105,.25); }
.pu-filter-sep { width: 1px; height: 22px; background: var(--c-border-md); flex-shrink: 0; }
@media (max-width: 600px) { .pu-filter-sep { display: none; } }
.pu-filter-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--c-text-3); white-space: nowrap; }

/* ── TABS ─────────────────────────────────────────────────────── */
.pu-tabs {
  display: flex; gap: 4px;
  margin-bottom: 14px;
}
.pu-tab {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px;
  border-radius: var(--r-md) var(--r-md) 0 0;
  font-size: 12.5px; font-weight: 600;
  border: 1px solid var(--c-border-md);
  border-bottom: none;
  background: var(--c-surface2);
  color: var(--c-text-3);
  cursor: pointer;
  transition: all var(--t-base);
  font-family: inherit;
  position: relative;
}
.pu-tab:hover { background: var(--c-surface); color: var(--c-text-2); }
.pu-tab.active {
  background: var(--c-surface);
  color: var(--c-blue);
  border-color: var(--c-border-md);
  border-bottom-color: var(--c-surface);
  box-shadow: 0 -2px 0 var(--c-blue) inset;
}
.pu-tab .tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: var(--r-pill);
  font-size: 10px; font-weight: 700;
  background: var(--c-red); color: #fff;
  line-height: 1;
}

/* ── TAB CONTENT PANEL ────────────────────────────────────────── */
.pu-tab-panel {
  background: var(--c-surface);
  border: 1px solid var(--c-border-md);
  border-radius: 0 var(--r-lg) var(--r-lg) var(--r-lg);
  padding: 16px;
  box-shadow: var(--sh-sm);
}

/* ── CUSTOM DT CONTROLS ──────────────────────────────────────── */
.pu-dt-length {
  background: var(--c-surface2);
  border: 1px solid var(--c-border-md);
  border-radius: var(--r-sm);
  color: var(--c-text-1);
  font-size: 12px; padding: 5px 9px;
  outline: none; cursor: pointer;
  font-family: inherit; height: 30px; min-width: 100px;
}
.pu-dt-length:focus { border-color: var(--c-blue); }
.pu-dt-search {
  display: flex; align-items: center; gap: 7px;
  background: var(--c-surface2);
  border: 1px solid var(--c-border-md);
  border-radius: var(--r-sm);
  padding: 6px 10px;
  min-width: 160px;
  transition: border-color var(--t-base), box-shadow var(--t-base);
}
.pu-dt-search:focus-within { border-color: var(--c-blue); box-shadow: var(--sh-focus); background: var(--c-surface); }
.pu-dt-search i { color: var(--c-text-3); font-size: 11px; flex-shrink: 0; }
.pu-dt-search input {
  background: none; border: none; outline: none;
  color: var(--c-text-1); font-size: 12px; width: 100%; font-family: inherit;
}
.pu-dt-search input::placeholder { color: var(--c-text-3); }

/* ── TABLE ────────────────────────────────────────────────────── */
.pu-table thead th {
  background: var(--c-surface2);
  font-size: 10.5px; text-transform: uppercase;
  letter-spacing: .5px; color: var(--c-text-3);
  border-bottom: 1px solid var(--c-border-md) !important;
  white-space: nowrap; font-weight: 700;
}
.pu-table tbody tr:hover td { background: var(--c-surface2); }

/* ── RETURNS SECTION HEADER ───────────────────────────────────── */
.pu-returns-header {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
  background: linear-gradient(135deg, var(--c-teal-dim), var(--c-blue-dim));
  border: 1px solid rgba(8,145,178,.18);
  border-radius: var(--r-md);
  margin-bottom: 12px;
}
.pu-returns-header .rh-icon {
  width: 36px; height: 36px;
  background: var(--c-teal); color: #fff;
  border-radius: var(--r-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.pu-returns-header .rh-title { font-size: 13px; font-weight: 700; color: var(--c-text-1); margin: 0; }
.pu-returns-header .rh-sub { font-size: 11px; color: var(--c-text-3); margin: 2px 0 0; }
.pu-returns-intransit {
  margin-left: auto;
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--c-red-dim);
  border: 1px solid rgba(220,38,38,.2);
  color: var(--c-red);
  border-radius: var(--r-pill);
  padding: 4px 10px;
  font-size: 11.5px; font-weight: 700;
  white-space: nowrap;
}
</style>
@endsection

@section('content')
<div class="pu-page">
<div class="mod-wrap">

    {{-- ── Header ── --}}
    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-cart-shopping me-2" style="color:#2980b9;"></i>შესყიდვები</h2>
            <p class="mod-subtitle">შესყიდვებისა და დაბრუნება/გაცვლის მართვა</p>
        </div>
        <div class="mod-actions">
            <button onclick="openInTransitSalesModal()" class="btn btn-info btn-sm">
                <i class="fa fa-list me-1"></i><span class="d-none d-sm-inline">ახალი გაყიდვები</span>
            </button>
            <button id="btn-new-purchase" onclick="openPurchaseModal()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი შესყიდვა</span>
            </button>
        </div>
    </div>

    {{-- ── Stats ── --}}
    <div class="pu-stats">
        <div class="pu-stat" style="--stat-line:var(--c-amber);">
            <div class="pu-stat-icon" style="background:var(--c-amber-dim);color:var(--c-amber);">
                <i class="fa fa-truck"></i>
            </div>
            <div class="pu-stat-label">გზაშია</div>
            <div class="pu-stat-value">{{ $purchaseInTransit }}</div>
            <div class="pu-stat-sub">მიმდინარე შესყიდვები</div>
        </div>
        <div class="pu-stat" style="--stat-line:var(--c-green);">
            <div class="pu-stat-icon" style="background:var(--c-green-dim);color:var(--c-green);">
                <i class="fa fa-warehouse"></i>
            </div>
            <div class="pu-stat-label">საწყობში</div>
            <div class="pu-stat-value">{{ $purchaseInWarehouse }}</div>
            <div class="pu-stat-sub">მიღებული ჯგუფები</div>
        </div>
        <div class="pu-stat" style="--stat-line:var(--c-teal);">
            <div class="pu-stat-icon" style="background:var(--c-teal-dim);color:var(--c-teal);">
                <i class="fa fa-rotate-left"></i>
            </div>
            <div class="pu-stat-label">დაბრუნება / გაცვლა</div>
            <div class="pu-stat-value">{{ $returnsTotal }}</div>
            <div class="pu-stat-sub">
                @if($returnsInTransit > 0)
                    <span style="color:var(--c-red);font-weight:700;">{{ $returnsInTransit }} გზაშია</span>
                @else
                    ყველა დამუშავებულია
                @endif
            </div>
        </div>
        <div class="pu-stat" style="--stat-line:var(--c-blue);">
            <div class="pu-stat-icon" style="background:var(--c-blue-dim);color:var(--c-blue);">
                <i class="fa fa-cart-shopping"></i>
            </div>
            <div class="pu-stat-label">სულ შესყიდვა</div>
            <div class="pu-stat-value">{{ $purchaseTotal }}</div>
            <div class="pu-stat-sub">ყველა ჯგუფი</div>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="pu-tabs">
        <button class="pu-tab active" id="tab-btn-regular" onclick="switchPurchaseTab('regular')" type="button">
            <i class="fa fa-cart-shopping" style="font-size:12px;"></i> შესყიდვები
        </button>
        <button class="pu-tab" id="tab-btn-returns" onclick="switchPurchaseTab('returns')" type="button">
            <i class="fa fa-rotate-left" style="font-size:12px;"></i> დაბრუნება / გაცვლა
            @if($returnsInTransit > 0)
                <span class="tab-badge">{{ $returnsInTransit }}</span>
            @endif
        </button>
    </div>

    {{-- ── Tab Panel ── --}}
    <div class="pu-tab-panel">

        {{-- ══ ჩვეულებრივი შესყიდვები ══ --}}
        <div id="tab-regular">
            {{-- Filter bar --}}
            <div class="pu-filter-bar">
                <span class="pu-filter-label">სტატუსი</span>
                <div class="pu-pill-group" id="purchase-status-filter">
                    <button type="button" class="pu-pill active-amber" data-status="2">
                        <i class="fa fa-truck" style="font-size:10px;"></i> გზაშია
                    </button>
                    <button type="button" class="pu-pill" data-status="3">
                        <i class="fa fa-check" style="font-size:10px;"></i> საწყობში
                    </button>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                    <div class="pu-dt-search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="pu-search-regular" placeholder="ძებნა...">
                    </div>
                    <select id="pu-length-regular" class="pu-dt-length">
                        <option value="10">10 ხაზი</option>
                        <option value="25">25 ხაზი</option>
                        <option value="50">50 ხაზი</option>
                        <option value="100">100 ხაზი</option>
                    </select>
                </div>
            </div>

            <table id="purchases-table" class="table pu-table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th>ნომერი</th>
                        <th style="width:52px"></th>
                        <th>პროდუქტი</th>
                        <th>კოდი</th>
                        <th>ზომა</th>
                        <th>რაოდ.</th>
                        <th>თვიტ. ღირ.($)</th>
                        <th>Price (₾)</th>
                        <th>სტატუსი</th>
                        <th>თარიღი</th>
                        <th>მოქმედება</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- ══ დაბრუნება / გაცვლა ══ --}}
        <div id="tab-returns" style="display:none;">
            {{-- Returns filter bar --}}
            <div class="pu-filter-bar mb-2">
                <span class="pu-filter-label">დაბრუნება / გაცვლა</span>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                    <div class="pu-dt-search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="pu-search-returns" placeholder="ძებნა...">
                    </div>
                    <select id="pu-length-returns" class="pu-dt-length">
                        <option value="10">10 ხაზი</option>
                        <option value="25">25 ხაზი</option>
                        <option value="50">50 ხაზი</option>
                        <option value="100">100 ხაზი</option>
                    </select>
                </div>
            </div>

            {{-- Returns header info --}}
            <div class="pu-returns-header">
                <div class="rh-icon"><i class="fa fa-rotate-left"></i></div>
                <div>
                    <p class="rh-title">დაბრუნება და გაცვლა</p>
                    <p class="rh-sub">გაყიდვებიდან წამოსული დაბრუნება/გაცვლის ორდერები</p>
                </div>
                @if($returnsInTransit > 0)
                    <div class="pu-returns-intransit">
                        <i class="fa fa-truck" style="font-size:10px;"></i>
                        {{ $returnsInTransit }} გზაშია
                    </div>
                @endif
            </div>

            <table id="returns-table" class="table pu-table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th>ნომერი</th>
                        <th style="width:52px"></th>
                        <th>პროდუქტი</th>
                        <th>კოდი</th>
                        <th>ზომა</th>
                        <th>რაოდ.</th>
                        <th>თვიტ. ღირ.($)</th>
                        <th>Price (₾)</th>
                        <th>სტატუსი</th>
                        <th>თარიღი</th>
                        <th>მოქმედება</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>{{-- /pu-tab-panel --}}

</div>{{-- /mod-wrap --}}
</div>{{-- /pu-page --}}


@include('purchases.form_purchase')

{{-- ══ Image Zoom Modal ══ --}}
<div class="modal fade" id="modal-img-zoom" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" style="z-index:10;"></button>
                <img id="zoom-img-src" src="" alt="" style="max-width:100%;max-height:80vh;border-radius:8px;">
            </div>
        </div>
    </div>
</div>

{{-- ══ Group View Modal ══ --}}
<div class="modal fade" id="modal-group-view" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold">📋 ჯგუფის შემადგენლობა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive" id="gv-body"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Group Receive Modal ══ --}}
<div class="modal fade" id="modal-group-receive" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#f39c12;color:#fff;border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📦 საწყობში მიღება</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="gr-group-id">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:52px"></th>
                                <th>პროდუქტი</th>
                                <th style="width:80px">ზომა</th>
                                <th style="width:75px" class="text-center">შეკვ.</th>
                                <th style="width:100px">
                                    <span class="text-success">✅ მიღებული</span>
                                </th>
                                <th style="width:100px">
                                    <span class="text-danger">❌ დაკარგ.</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="gr-lines-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success" id="btn-gr-save" onclick="submitGroupReceive()">
                    <i class="fa fa-check me-1"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ In-Transit Sales Modal ══ --}}
<div class="modal fade" id="modal-in-transit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#0ea5e9;color:#fff;">
                <h5 class="modal-title fw-bold">
                    <i class="fa fa-list me-2"></i>ახალი გაყიდვები
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="in-transit-loading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status"></div>
                </div>
                <div class="table-responsive" id="in-transit-body" style="display:none;">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:52px"></th>
                                <th>პროდუქტი</th>
                                <th>კოდი</th>
                                <th style="width:70px">ზომა</th>
                                <th style="width:70px" class="text-center">რაოდ.</th>
                                <th style="width:90px" class="text-end">ფასი (₾)</th>
                            </tr>
                        </thead>
                        <tbody id="in-transit-rows"></tbody>
                    </table>
                </div>
                <div id="in-transit-empty" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa fa-check-circle fa-2x mb-2 text-success"></i><br>გზაში გაყიდვები არ არის
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">დახურვა</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-auto-purchase" onclick="autoPurchaseFromInTransit()" style="display:none;">
                    <i class="fa fa-cart-plus me-1"></i> ავტომატური შესყიდვა
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(function() {

    // ══ IMAGE ZOOM ══
    window.zoomPurchaseImg = function(el) {
        $('#zoom-img-src').attr('src', el.src);
        new bootstrap.Modal(document.getElementById('modal-img-zoom')).show();
    };

    // ══ TAB SWITCHING ══
    var currentTab = 'regular';

    window.switchPurchaseTab = function(tab) {
        currentTab = tab;

        $('.pu-tab').removeClass('active');
        $('#tab-btn-' + tab).addClass('active');

        $('#tab-regular, #tab-returns').hide();
        $('#tab-' + tab).show();

        $('#btn-new-purchase').toggle(tab === 'regular');

        if (tab === 'regular') {
            purchasesTable.columns.adjust().draw(false);
        } else {
            returnsTable.columns.adjust().draw(false);
        }
    };

    // ══ PURCHASES TABLE ══
    var purchaseStatusFilter = '2';

    var purchasesTable = $('#purchases-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
        dom: 'rtip',
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: function(d) {
                d.type          = 'regular';
                d.status_filter = purchaseStatusFilter;
            }
        },
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2 },
            { data: 'show_photo',      name: 'show_photo',      orderable: false, responsivePriority: 3 },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 1, orderable: false },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9 },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 4 },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 5 },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7 },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8 },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 6 },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 9 },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2 },
            { data: 'is_return_purchase', visible: false },
            { data: 'group_items_json',   visible: false },
        ]
    });

    // ══ CUSTOM DATATABLE CONTROLS ══
    $('#pu-search-regular').on('keyup', function() { purchasesTable.search(this.value).draw(); });
    $('#pu-length-regular').on('change', function() { purchasesTable.page.len(+this.value).draw(); });
    $('#pu-search-returns').on('keyup', function() { returnsTable.search(this.value).draw(); });
    $('#pu-length-returns').on('change', function() { returnsTable.page.len(+this.value).draw(); });

    // ══ STATUS FILTER (pill style) ══
    $('#purchase-status-filter .pu-pill').on('click', function() {
        purchaseStatusFilter = $(this).data('status').toString();

        $('#purchase-status-filter .pu-pill')
            .removeClass('active-amber active-green');

        if (purchaseStatusFilter === '2') {
            $(this).addClass('active-amber');
        } else {
            $(this).addClass('active-green');
        }

        purchasesTable.ajax.reload();
    });

    // ══ GROUP VIEW ══
    window.openGroupView = function(groupId) {
        $.get("{{ url('purchases/group') }}/" + groupId + "/items", function(items) {
            items = items || [];

            var html = '<table class="table table-sm table-bordered mb-0">'
             + '<thead class="table-light"><tr>'
             + '<th style="width:52px"></th>'
             + '<th>პროდუქტი</th><th>კოდი</th><th>ზომა</th>'
             + '<th class="text-center">შეკვეთა</th>'
             + '<th class="text-center">გზაშია</th>'
             + '<th class="text-center">დაკარგ.</th>'
             + '<th class="text-end" style="color:#7c3aed;">თვიტ.($)</th>'
             + '</tr></thead><tbody>';

            items.forEach(function(it) {
                var orig      = it.original_qty || it.quantity || 0;
                var remaining = it.status_id === 2 ? (it.quantity || 0) : 0;
                var lost      = it.lost_qty || 0;
                var cost      = it.cost_price || 0;

                var remainCell = remaining > 0
                    ? '<span class="text-warning fw-bold">' + remaining + '</span>'
                    : '<span class="text-muted">—</span>';

                var lostCell = lost > 0
                    ? '<span class="text-danger fw-bold">' + lost + '</span>'
                    : '<span class="text-muted">—</span>';

                var costCell = cost > 0
                    ? '<span style="color:#7c3aed;font-weight:700;">$' + cost.toFixed(2) + '</span>'
                    : '<span class="text-muted">—</span>';

                var imgCell = it.product_image
                    ? '<img src="' + it.product_image + '" style="width:44px;height:44px;object-fit:cover;border-radius:4px;">'
                    : '<span class="text-muted" style="font-size:18px;">📦</span>';

                html += '<tr>'
                     +  '<td class="text-center align-middle">' + imgCell + '</td>'
                     +  '<td class="fw-semibold align-middle">' + (it.product_name||'N/A') + '</td>'
                     +  '<td class="text-muted align-middle" style="font-size:12px;">' + (it.product_code||'—') + '</td>'
                     +  '<td class="align-middle">' + (it.product_size||'—') + '</td>'
                     +  '<td class="text-center fw-bold align-middle">' + orig + '</td>'
                     +  '<td class="text-center align-middle">' + remainCell + '</td>'
                     +  '<td class="text-center align-middle">' + lostCell + '</td>'
                     +  '<td class="text-end align-middle">' + costCell + '</td>'
                     +  '</tr>';
            });

            html += '</tbody></table>';
            $('#gv-body').html(html);
            new bootstrap.Modal(document.getElementById('modal-group-view')).show();
        });
    };

    // ══ RETURNS TABLE ══
    var returnsTable = $('#returns-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
        dom: 'rtip',
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: { type: 'returns' }
        },
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2 },
            { data: 'show_photo',      name: 'show_photo',      orderable: false, responsivePriority: 3 },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 1, orderable: false },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9 },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 4 },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 5 },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7 },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8 },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 6 },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 9 },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2 },
            { data: 'is_return_purchase', visible: false },
            { data: 'group_items_json',   visible: false },
        ],
        createdRow: function(row) {
            $(row).css('background-color', '#d9edf7');
        }
    });

    // ══ MULTI-LINE PURCHASE FORM ══
    var purchaseLineIndex = 0;
    var productOptionsTpl = document.getElementById('tpl-product-options').innerHTML;

    window.addPurchaseLine = function(defaults) {
        var idx = purchaseLineIndex++;

        var $prodSel = $('<select required>')
            .addClass('form-select form-select-sm line-product w-100')
            .attr('name', 'items[' + idx + '][product_id]')
            .html(productOptionsTpl);

        var $sizeSel = $('<select required>')
            .addClass('form-select form-select-sm line-size')
            .attr('name', 'items[' + idx + '][product_size]')
            .append('<option value="">—</option>');

        var $qty = $('<input type="number" required>')
            .addClass('form-control form-control-sm line-qty')
            .attr({ name: 'items[' + idx + '][quantity]', min: 1, value: 1 });

        var $priceUsa = $('<input type="number">')
            .addClass('form-control form-control-sm line-price-usa')
            .attr({ name: 'items[' + idx + '][price_usa]', step: '0.01', min: 0, placeholder: '0.00' });

        var $transport = $('<input type="number">')
            .addClass('form-control form-control-sm line-transport')
            .attr({ name: 'items[' + idx + '][transport]', step: '0.01', min: 0, placeholder: '0.00' });

        var $priceGeo = $('<input type="text" readonly>')
            .addClass('form-control form-control-sm line-price-geo bg-light')
            .attr('name', 'items[' + idx + '][price_georgia]')
            .attr('placeholder', '0.00');

        var $fifo = $('<small class="line-fifo">');

        var $removeBtn = $('<button type="button">')
            .addClass('btn btn-outline-danger btn-sm remove-line p-1')
            .html('<i class="fa fa-times"></i>');

        var $tr = $('<tr class="purchase-line">').append(
            $('<td>').append($prodSel),
            $('<td>').append($sizeSel),
            $('<td>').append($qty),
            $('<td>').append($priceUsa).append($fifo),
            $('<td>').append($transport),
            $('<td>').append($priceGeo),
            $('<td class="text-center">').append($removeBtn)
        );

        $('#purchase-lines-body').append($tr);

        $prodSel.select2({
            dropdownParent: $('#modal-purchase'),
            width: '100%',
            templateResult: function(opt) {
                if (!opt.id) return opt.text;
                var img = $(opt.element).attr('data-image');
                var $s = $('<span style="display:flex;align-items:center;gap:8px;">');
                if (img) $s.append($('<img>').attr('src', img).css({ width: '32px', height: '32px', objectFit: 'cover', borderRadius: '3px', flexShrink: 0 }));
                $s.append(document.createTextNode(opt.text));
                return $s;
            },
            templateSelection: function(opt) {
                if (!opt.id) return opt.text;
                var img = $(opt.element).attr('data-image');
                if (!img) return opt.text;
                var $s = $('<span style="display:flex;align-items:center;gap:6px;">');
                $s.append($('<img>').attr('src', img).css({ width: '24px', height: '24px', objectFit: 'cover', borderRadius: '2px', flexShrink: 0 }));
                $s.append(document.createTextNode(opt.text));
                return $s;
            }
        });

        if (defaults) {
            $prodSel.val(defaults.product_id || '').trigger('change.select2');
            var opt   = $prodSel.find(':selected');
            var sizes = (opt.attr('data-sizes') || '').toString();
            if (sizes) {
                sizes.split(',').forEach(function(s) {
                    s = s.trim();
                    if (s) $sizeSel.append('<option value="' + s + '">' + s + '</option>');
                });
            }
            $sizeSel.val(defaults.product_size || '');
            $qty.val(defaults.quantity || 1);
            $priceUsa.val(defaults.price_usa || '');
            $transport.val(defaults.transport != null ? defaults.transport : 0);
            $priceGeo.val(defaults.price_georgia ? parseFloat(defaults.price_georgia).toFixed(2) : '');
        } else {
            // ახალი row — transport-ი წინა row-ებიდან გადმოვა
            var existingTransport = parseFloat($('#purchase-lines-body .line-transport').first().val()) || 0;
            if (existingTransport > 0) $transport.val(existingTransport);
        }

        updateRemoveButtons();
    };

    function updateRemoveButtons() {
        var $rows = $('#purchase-lines-body .purchase-line');
        $rows.find('.remove-line').toggle($rows.length > 1);
    }

    $(document).on('change', '#purchase-lines-body .line-product', function() {
        var $tr  = $(this).closest('tr');
        var opt  = $(this).find(':selected');
        var sizes = (opt.attr('data-sizes') || '').toString();
        var geo   = opt.attr('data-price-ge') || 0;

        var $sz = $tr.find('.line-size').empty().append('<option value="">—</option>');
        if (sizes) {
            sizes.split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sz.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $tr.find('.line-price-geo').val(geo ? parseFloat(geo).toFixed(2) : '');
        $tr.find('.line-fifo').text('');
    });

    $(document).on('change', '#purchase-lines-body .line-size', function() {
        var $tr    = $(this).closest('tr');
        var prodId = $tr.find('.line-product').val();
        var size   = $(this).val();
        if (prodId && size) {
            $.get("{{ route('warehouse.stockInfo') }}", { product_id: prodId, size: size }, function(d) {
                $tr.find('.line-fifo').text(d.fifo_cost ? 'FIFO: $' + d.fifo_cost : '');
            });
        }
    });

    $(document).on('click', '#purchase-lines-body .remove-line', function() {
        var $tr = $(this).closest('tr');
        $tr.find('.line-product').select2('destroy');
        $tr.remove();
        updateRemoveButtons();
    });

    // ── ტრანსპ. სინქრონიზაცია ყველა row-ზე ─────────────────────────
    $(document).on('input', '#purchase-lines-body .line-transport', function() {
        var val = $(this).val();
        $('#purchase-lines-body .line-transport').not(this).val(val);
    });

    // ══ MODAL OPEN ══
    window.openPurchaseModal = function() {
        purchaseLineIndex = 0;
        $('#purchase_id').val('');
        $('input[name="_method"]', '#form-purchase').val('POST');
        $('#purchase-modal-title').text('📦 ახალი შესყიდვა');
        $('#purchase-lines-body').empty();
        $('#purchase_comment').val('');
        $('#purchase_courier_section').hide();
        $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
        $('#btn-add-line').show();
        addPurchaseLine();
        $('#modal-purchase').modal('show');
    };

    // ══ EDIT ══
    window.editPurchase = function(id) {
        $.get("{{ url('purchases') }}/" + id + "/edit", function(data) {
            purchaseLineIndex = 0;
            $('#purchase_id').val(data.id);
            $('input[name="_method"]', '#form-purchase').val('PATCH');
            $('#purchase-modal-title').text('✏️ ' + (data.order_number || '#' + data.id));
            $('#purchase-lines-body').empty();
            $('#btn-add-line').hide();

            $('#purchase_comment').val(data.comment || '');

            if (data.is_return_purchase) {
                $('#purchase_courier_section').show();
                var cType = 'none';
                if ((data.courier_price_tbilisi || 0) > 0) cType = 'tbilisi';
                else if ((data.courier_price_region  || 0) > 0) cType = 'region';
                else if ((data.courier_price_village || 0) > 0) cType = 'village';
                $('input[name="purchase_courier_type"][value="' + cType + '"]').prop('checked', true);
            } else {
                $('#purchase_courier_section').hide();
                $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
            }

            addPurchaseLine({
                product_id:   data.product_id,
                product_size: data.product_size,
                quantity:     data.quantity,
                price_usa:    data.price_usa,
                transport:    data.is_return_purchase ? 0 : (data.courier_price_international || 0),
                price_georgia: data.price_georgia || 0,
            });

            var courierCount = data.courier_count || 0;
            if (courierCount > 0) {
                var $tr = $('#purchase-lines-body .purchase-line');
                $tr.find('.line-product, .line-size, .line-price-usa, .line-transport')
                   .prop('disabled', true).css('background', '#f5f5f5');
                $tr.find('.line-qty').attr('min', courierCount).css('background', '#fff8e1');
                $('#purchase-courier-lock-msg').remove();
                var lockMsg = '<div id="purchase-courier-lock-msg" style="background:#fff3cd;border:1px solid #ffc107;' +
                    'border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12px;color:#856404;">' +
                    '⚠️ <strong>' + courierCount + ' ერთეული</strong> გაყიდულია — პროდუქტი/ზომა/ფასი/ტრანსპ. ვერ შეიცვლება.</div>';
                $('#form-purchase .modal-body').prepend(lockMsg);
            }

            $('#modal-purchase').modal('show');
        });
    };

    $('#modal-purchase').on('hidden.bs.modal', function() {
        $('#purchase-lines-body .line-product').each(function() {
            if ($(this).data('select2')) $(this).select2('destroy');
        });
        $('#purchase-lines-body .line-product, #purchase-lines-body .line-size, ' +
          '#purchase-lines-body .line-price-usa, #purchase-lines-body .line-transport')
            .prop('disabled', false).css('background', '');
        $('#purchase-courier-lock-msg').remove();
        $('#btn-add-line').show();
        $('#purchase_courier_section').hide();
        $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
    });

    // ══ DELETE ══
    window.deletePurchase = function(id) {
        swal({
            title: 'დარწმუნებული ხარ?', text: 'შესყიდვა წაიშლება!',
            type: 'warning', showCancelButton: true,
            confirmButtonColor: '#dd4b39',
            cancelButtonText: 'გაუქმება', confirmButtonText: 'წაშლა'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('purchases') }}/" + id, type: 'POST',
                data: { _method: 'DELETE', _token: "{{ csrf_token() }}" },
                success: function(res) {
                    purchasesTable.ajax.reload();
                    returnsTable.ajax.reload();
                    refreshPurchaseStats();
                    swal({ title: 'წაიშალა!', text: res.message, type: 'success', timer: 1500 });
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                    swal({ title: 'შეცდომა', text: msg, type: 'error' });
                }
            });
        });
    };

    // ══ SUBMIT ══
    $('#form-purchase').on('submit', function(e) {
        e.preventDefault();
        var id  = $('#purchase_id').val();
        var url = id ? "{{ url('purchases') }}/" + id : "{{ url('purchases') }}";

        // ── ვალიდაცია: ფასი ($) და ტრანსპ. ($) > 0 ──────────────────
        var hasError = false;
        $('#purchase-lines-body .purchase-line').each(function() {
            var price     = parseFloat($(this).find('.line-price-usa').val()) || 0;
            var transport = parseFloat($(this).find('.line-transport').val()) || 0;
            if (price <= 0 || transport <= 0) { hasError = true; return false; }
        });
        if (hasError) {
            swal('შეცდომა', 'ყველა პროდუქტს უნდა ჰქონდეს ფასი ($) და ტრანსპ. ($) — ორივე 0-ზე მეტი', 'error');
            return;
        }

        var $locked = $(this).find(':disabled').prop('disabled', false);
        var formData;

        if (id) {
            var $tr = $('#purchase-lines-body .purchase-line').first();
            formData = {
                _method:                     'PATCH',
                _token:                      "{{ csrf_token() }}",
                order_type:                  'purchase',
                product_id:                  $tr.find('.line-product').val(),
                product_size:                $tr.find('.line-size').val(),
                quantity:                    $tr.find('.line-qty').val(),
                price_usa:                   $tr.find('.line-price-usa').val() || 0,
                courier_price_international: $tr.find('.line-transport').val() || 0,
                price_georgia:               $tr.find('.line-price-geo').val() || 0,
                purchase_courier_type:       $('input[name="purchase_courier_type"]:checked').val() || 'none',
                comment:                     $('#purchase_comment').val(),
            };
        } else {
            formData = $(this).serialize();
        }

        $locked.prop('disabled', true);

        $.ajax({
            url: url, type: 'POST',
            data: formData,
            success: function(res) {
                $('#modal-purchase').modal('hide');
                purchasesTable.ajax.reload();
                returnsTable.ajax.reload();
                refreshPurchaseStats();
                swal({ title: '✅', text: res.message, type: 'success', timer: 1800 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            }
        });
    });

    // ══ GROUP RECEIVE ══
    window.openGroupReceive = function(groupId) {
        $('#gr-lines-body').empty();
        $('#gr-group-id').val(groupId);
        $('#btn-gr-save').prop('disabled', false);

        $.get("{{ url('purchases/group') }}/" + groupId + "/items", function(allItems) {
            var items = (allItems || []).filter(function(it) { return it.status_id === 2; });
            if (!items.length) {
                swal('ინფო', 'ამ ჯგუფში სტატუს=2 ორდერი არ მოიძებნა', 'info');
                return;
            }
            items.forEach(function(it) {
                var $imgCell = $('<td class="text-center align-middle" style="width:52px;">');
                if (it.product_image) {
                    $imgCell.append($('<img>').attr('src', it.product_image)
                        .css({ width: '44px', height: '44px', objectFit: 'cover', borderRadius: '4px' }));
                } else {
                    $imgCell.text('📦');
                }
                var $tr = $('<tr data-order-id="' + it.id + '">').append(
                    $imgCell,
                    $('<td class="fw-semibold align-middle">').text(it.product_name),
                    $('<td class="align-middle">').text(it.product_size || '—'),
                    $('<td class="text-center fw-bold text-muted gr-ordered">').text(it.quantity),
                    $('<td>').append(
                        $('<input type="number" class="form-control form-control-sm text-center gr-received">')
                            .val(it.quantity).attr({ min: 0, max: it.quantity })
                    ),
                    $('<td>').append(
                        $('<input type="number" class="form-control form-control-sm text-center gr-lost">')
                            .val(0).attr({ min: 0, max: it.quantity })
                    )
                );
                $('#gr-lines-body').append($tr);
            });
            new bootstrap.Modal(document.getElementById('modal-group-receive')).show();
        });
    };

    $(document).on('input', '.gr-received, .gr-lost', function() {
        var $tr      = $(this).closest('tr');
        var ordered  = parseInt($tr.find('.gr-ordered').text()) || 0;
        var received = parseInt($tr.find('.gr-received').val()) || 0;
        var lost     = parseInt($tr.find('.gr-lost').val())     || 0;
        if (received + lost > ordered) {
            $(this).addClass('is-invalid');
        } else {
            $tr.find('.gr-received, .gr-lost').removeClass('is-invalid');
        }
    });

    window.submitGroupReceive = function() {
        var groupId = $('#gr-group-id').val();
        var items = [];
        var valid = true;

        $('#gr-lines-body tr').each(function() {
            var orderId  = $(this).data('order-id');
            var received = parseInt($(this).find('.gr-received').val()) || 0;
            var lost     = parseInt($(this).find('.gr-lost').val())     || 0;
            var ordered  = parseInt($(this).find('.gr-ordered').text()) || 0;

            if (received + lost > ordered) { valid = false; }
            items.push({ order_id: orderId, received_qty: received, lost_qty: lost });
        });

        if (!valid) { swal('შეცდომა', 'ერთ-ერთი ხაზის ჯამი აღემატება შეკვეთილ რაოდენობას', 'error'); return; }

        $('#btn-gr-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ url('purchases/group') }}/" + groupId + "/partial-receive",
            type: 'POST',
            data: { items: items, _token: "{{ csrf_token() }}" },
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-group-receive')).hide();
                purchasesTable.ajax.reload();
                returnsTable.ajax.reload();
                refreshPurchaseStats();
                swal({ title: '✅', text: res.message, type: 'success' });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-gr-save').prop('disabled', false).html('<i class="fa fa-check me-1"></i> დადასტურება');
            }
        });
    };

    // ══ IN-TRANSIT SALES ══
    var inTransitItems = [];

    window.openInTransitSalesModal = function() {
        inTransitItems = [];
        $('#in-transit-loading').show();
        $('#in-transit-body, #in-transit-empty').hide();
        $('#btn-auto-purchase').hide();
        $('#in-transit-rows').empty();
        new bootstrap.Modal(document.getElementById('modal-in-transit')).show();

        $.get("{{ route('purchases.inTransitSales') }}", function(items) {
            $('#in-transit-loading').hide();

            if (!items || !items.length) {
                $('#in-transit-empty').show();
                return;
            }

            inTransitItems = items;

            items.forEach(function(it) {
                var img = it.image_url
                    ? '<img src="' + it.image_url + '" style="width:44px;height:44px;object-fit:cover;border-radius:4px;cursor:zoom-in;" onclick="zoomPurchaseImg(this)">'
                    : '<span class="text-muted">—</span>';
                var price = it.price_geo ? parseFloat(it.price_geo).toFixed(2) + ' ₾' : '—';
                $('#in-transit-rows').append(
                    '<tr>'
                    + '<td class="text-center">' + img + '</td>'
                    + '<td class="fw-semibold">' + $('<span>').text(it.product_name).html() + '</td>'
                    + '<td class="text-muted">' + $('<span>').text(it.product_code).html() + '</td>'
                    + '<td class="text-center">' + (it.product_size || '—') + '</td>'
                    + '<td class="text-center fw-bold">' + it.quantity + '</td>'
                    + '<td class="text-end">' + price + '</td>'
                    + '</tr>'
                );
            });

            $('#in-transit-body').show();
            $('#btn-auto-purchase').show();
        }).fail(function() {
            $('#in-transit-loading').hide();
            $('#in-transit-empty').show();
        });
    };

    window.autoPurchaseFromInTransit = function() {
        if (!inTransitItems.length) return;

        bootstrap.Modal.getInstance(document.getElementById('modal-in-transit')).hide();

        purchaseLineIndex = 0;
        $('#purchase_id').val('');
        $('input[name="_method"]', '#form-purchase').val('POST');
        $('#purchase-modal-title').text('📦 ავტომატური შესყიდვა');
        $('#purchase-lines-body').empty();
        $('#purchase_comment').val('');
        $('#purchase_courier_section').hide();
        $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
        $('#btn-add-line').show();

        inTransitItems.forEach(function(it) {
            addPurchaseLine({
                product_id:    it.product_id,
                product_size:  it.product_size,
                quantity:      it.quantity,
                price_georgia: it.price_geo || '',
            });
        });

        $('#modal-purchase').modal('show');
    };

    // ══ STATS REFRESH ══
    function refreshPurchaseStats() {
        $.get("{{ route('purchases.stats') }}", function(d) {
            // stat cards
            $('.pu-stat-value').eq(0).text(d.in_transit);
            $('.pu-stat-value').eq(1).text(d.in_warehouse);
            $('.pu-stat-value').eq(2).text(d.returns_total);
            $('.pu-stat-value').eq(3).text(d.purchase_total);

            // returns stat card sub-text
            var $returnsSub = $('.pu-stat').eq(2).find('.pu-stat-sub');
            if (d.returns_in_transit > 0) {
                $returnsSub.html('<span style="color:var(--c-red);font-weight:700;">' + d.returns_in_transit + ' გზაშია</span>');
            } else {
                $returnsSub.text('ყველა დამუშავებულია');
            }

            // tab badge
            var $tabBtn = $('#tab-btn-returns');
            $tabBtn.find('.tab-badge').remove();
            if (d.returns_in_transit > 0) {
                $tabBtn.append('<span class="tab-badge">' + d.returns_in_transit + '</span>');
            }

            // returns header intransit badge
            var $rh = $('.pu-returns-intransit');
            if (d.returns_in_transit > 0) {
                if ($rh.length) {
                    $rh.html('<i class="fa fa-truck" style="font-size:10px;"></i> ' + d.returns_in_transit + ' გზაშია');
                } else {
                    $('.pu-returns-header').append(
                        '<div class="pu-returns-intransit"><i class="fa fa-truck" style="font-size:10px;"></i> ' + d.returns_in_transit + ' გზაშია</div>'
                    );
                }
            } else {
                $rh.remove();
            }
        });
    }

});
</script>
@endsection