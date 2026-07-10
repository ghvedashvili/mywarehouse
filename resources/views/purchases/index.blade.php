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

/* Mobile composite cell: hidden on desktop */
.pu-cell-mobile { display: none; }
/* Group-view mobile extras: hidden on desktop */
.gv-mob-extras  { display: none; }
.gv-mob-thumb   { display: none; }
.gv-name-hdr    { display: none; }

/* ═══════════════ MOBILE CARD VIEW ≤767px ═══════════════ */
@media (max-width: 767px) {

  .pu-page { padding: 6px 12px 80px !important; }
  .pu-page .mod-header { padding: 0 0 10px !important; }
  .pu-page .mod-header > div:first-child { display: none !important; }
  .pu-page .mod-header .mod-actions { width: 100%; display: flex; gap: 8px; }
  .pu-page .mod-header .mod-actions .btn { flex: 1; justify-content: center; }

  .pu-stats { grid-template-columns: repeat(2,1fr); gap: 8px; margin-bottom: 10px; }
  .pu-stat { padding: 10px 12px; }
  .pu-stat-value { font-size: 17px; }

  /* Tab panel stripped */
  .pu-tab-panel {
    background: transparent !important;
    border: none !important; box-shadow: none !important; padding: 0 !important;
    border-radius: 0 !important;
  }

  /* Filter bar stacked */
  .pu-filter-bar { flex-direction: column !important; gap: 8px !important; }
  .pu-filter-bar .ms-auto { width: 100% !important; }
  .pu-filter-bar .pu-dt-search { flex: 1; min-width: 0; }
  .pu-filter-bar .pu-dt-length { width: 100%; }
  .pu-filter-sep { display: none !important; }

  /* Tables → card blocks */
  #purchases-table, #returns-table {
    display: block !important; width: 100% !important;
  }
  #purchases-table thead, #returns-table thead { display: none !important; }
  #purchases-table tbody, #returns-table tbody { display: block !important; }
  #purchases-table tfoot, #returns-table tfoot { display: none !important; }

  /* Row = card (CSS grid) */
  #purchases-table tbody tr,
  #returns-table tbody tr {
    display: grid !important;
    grid-template-columns: 1fr auto;
    background: var(--c-surface);
    border-radius: var(--r-md) !important;
    margin: 0 0 10px !important;
    box-shadow: var(--sh-sm) !important;
    border: 1px solid var(--c-border-md) !important;
    overflow: hidden;
  }
  #returns-table tbody tr { border-left: 3px solid var(--c-teal) !important; }

  /* All cells default */
  #purchases-table tbody td,
  #returns-table tbody td {
    display: block !important;
    grid-column: 1 / -1;
    padding: 8px 12px !important;
    border: none !important;
    font-size: 13px;
  }

  /* Hidden cells */
  #purchases-table tbody td.pu-td-hide,
  #returns-table tbody td.pu-td-hide { display: none !important; }

  /* Product cell: desktop version hidden, mobile version shown */
  .pu-cell-desktop { display: none !important; }
  .pu-cell-mobile  { display: block; }

  /* ── Row 1: ORDER NUMBER (left) ── */
  #purchases-table tbody td.pu-td-num,
  #returns-table tbody td.pu-td-num {
    display: flex !important;
    grid-column: 1; grid-row: 1;
    align-items: center; gap: 6px;
    background: var(--c-surface2) !important;
    border-bottom: 1px solid var(--c-border) !important;
    padding: 8px 12px !important;
    font-weight: 700; font-size: 12.5px;
  }

  /* ── Row 1: STATUS (right) ── */
  #purchases-table tbody td.pu-td-status,
  #returns-table tbody td.pu-td-status {
    display: flex !important;
    grid-column: 2; grid-row: 1;
    align-items: center; justify-content: flex-end;
    background: var(--c-surface2) !important;
    border-bottom: 1px solid var(--c-border) !important;
    padding: 6px 10px !important;
    white-space: nowrap;
  }

  /* ── Row 2 (returns only): CUSTOMER ── */
  #returns-table tbody td.pu-td-customer {
    display: flex !important;
    grid-column: 1 / -1; grid-row: 2;
    align-items: center; gap: 6px;
    padding: 6px 12px !important;
    font-size: 11.5px; color: var(--c-text-2);
    border-bottom: 1px solid var(--c-border) !important;
  }

  /* ── Row 2 / Row 3: PRODUCT composite ── */
  #purchases-table tbody td.pu-td-product {
    display: block !important;
    grid-column: 1 / -1; grid-row: 2;
    padding: 10px 12px !important;
    border-bottom: 1px solid var(--c-border) !important;
  }
  #returns-table tbody td.pu-td-product {
    display: block !important;
    grid-column: 1 / -1; grid-row: 3;
    padding: 10px 12px !important;
    border-bottom: 1px solid var(--c-border) !important;
  }

  /* ── Footer: DATE ── */
  #purchases-table tbody td.pu-td-date {
    display: flex !important; grid-column: 1; grid-row: 3;
    align-items: center; font-size: 11px; color: var(--c-text-3);
    background: var(--c-surface2) !important;
    border-top: 1px solid var(--c-border) !important; padding: 7px 12px !important;
  }
  #returns-table tbody td.pu-td-date {
    display: flex !important; grid-column: 1; grid-row: 4;
    align-items: center; font-size: 11px; color: var(--c-text-3);
    background: var(--c-surface2) !important;
    border-top: 1px solid var(--c-border) !important; padding: 7px 12px !important;
  }

  /* ── Footer: ACTION ── */
  #purchases-table tbody td.pu-td-action {
    display: flex !important; grid-column: 2; grid-row: 3;
    align-items: center; justify-content: flex-end;
    gap: 5px; flex-wrap: wrap;
    background: var(--c-surface2) !important;
    border-top: 1px solid var(--c-border) !important; padding: 5px 10px !important;
  }
  #returns-table tbody td.pu-td-action {
    display: flex !important; grid-column: 2; grid-row: 4;
    align-items: center; justify-content: flex-end;
    gap: 5px; flex-wrap: wrap;
    background: var(--c-surface2) !important;
    border-top: 1px solid var(--c-border) !important; padding: 5px 10px !important;
  }

  /* ── Product cell inner ── */
  .pu-product-cell { display: flex; align-items: flex-start; gap: 10px; }
  .pu-prod-thumb { flex-shrink: 0; }
  .pu-prod-thumb img {
    width: 58px !important; height: 58px !important;
    object-fit: cover !important; border-radius: 8px !important;
    border: 1px solid var(--c-border-md); display: block !important;
  }
  .pu-group-badge {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    width: 58px; height: 58px; flex-shrink: 0;
    background: var(--c-blue-dim);
    border: 1px solid rgba(37,99,235,.25); border-radius: 8px;
    color: var(--c-blue); gap: 2px; text-align: center;
  }
  .pu-group-badge i { font-size: 15px; }
  .pu-group-badge span { font-size: 10px; font-weight: 700; }
  .pu-prod-info { flex: 1; min-width: 0; }
  .pu-prod-name {
    font-weight: 600; font-size: 13px; line-height: 1.35;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  }
  .pu-prod-meta { font-size: 11px; color: var(--c-text-3); margin-top: 3px; }
  .pu-prod-price {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-top: 5px; font-size: 11.5px;
  }

  /* ── Modal tables → card style (group-view, group-receive, in-transit) ── */
  .modal-card-table .table-responsive { overflow: visible !important; }
  .modal-card-table table { min-width: 0 !important; }
  .modal-card-table thead { display: none !important; }
  .modal-card-table table,
  .modal-card-table tbody { display: block !important; width: 100% !important; }
  .modal-card-table tbody tr {
    display: block !important;
    background: #fff;
    border-radius: 10px;
    margin: 0 0 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    border: 1px solid #e5e7eb !important;
    overflow: hidden;
  }
  .modal-card-table .mc-td-img { display: none !important; }
  .modal-card-table .mc-td-name {
    display: block !important;
    background: #f8fafc;
    padding: 10px 13px !important;
    border-bottom: 1px solid #eff3f8 !important;
    font-size: 14px; font-weight: 600; color: #1e293b;
  }
  .modal-card-table td[data-label] {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    padding: 8px 13px !important;
    border-bottom: 1px solid #f8fafc !important;
    border-top: none !important;
    font-size: 13px;
    min-height: 38px;
  }
  .modal-card-table td[data-label]::before {
    content: attr(data-label);
    font-size: 10px; font-weight: 700;
    color: #94a3b8; text-transform: uppercase;
    letter-spacing: .4px; flex-shrink: 0; margin-right: 10px;
  }
  .modal-card-table .gr-received,
  .modal-card-table .gr-lost,
  .modal-card-table .transit-ml-input {
    width: 80px !important; font-size: 16px !important;
    text-align: center; padding: 6px !important;
  }
  /* already-received rows in group-receive */
  #gr-lines-body tr.table-success { border-left: 3px solid #16a34a !important; }

  /* ── Group Receive — card layout ── */
  #gr-lines-body .gv-mob-extras { display: block !important; }
  #gr-lines-body td.mc-td-img { display: none !important; }
  #gr-lines-body td.mc-td-sz  { display: none !important; }

  /* tr → flex wrap: name fills first row, stat cells fill second row */
  #gr-lines-body tr {
    display: flex !important;
    flex-wrap: wrap;
    align-items: stretch;
  }
  #gr-lines-body td.mc-td-name {
    flex: 0 0 100% !important;
    display: block !important;
  }

  /* Already-received: hide desktop stat cells (status shown in name) */
  #gr-lines-body tr.table-success td[data-label] { display: none !important; }

  /* Pending: stat cells in one horizontal row */
  #gr-lines-body td.gr-stat {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 4px !important;
    border-right: 1px solid #e9edf4 !important;
    border-top: 1px solid #eff3f8 !important;
    border-bottom: none !important;
    background: #f8fafc;
    text-align: center;
    min-height: 64px;
  }
  #gr-lines-body td.gr-stat:last-child { border-right: none !important; }
  #gr-lines-body td.gr-stat[data-label]::before {
    display: block !important;
    margin-right: 0 !important;
    margin-bottom: 6px;
    font-size: 9px;
  }
  #gr-lines-body .gr-received,
  #gr-lines-body .gr-lost {
    width: 65px !important;
    font-size: 16px !important;
    text-align: center !important;
    padding: 6px 4px !important;
  }

  /* ── Shared: photo + name header ── */
  .gv-desktop-name { display: none !important; }

  .gv-mob-extras .gv-name-hdr {
    display: flex !important;
    align-items: flex-start;
    gap: 10px;
  }
  .gv-mob-thumb {
    display: block !important;
    width: 44px !important; height: 44px !important;
    object-fit: cover;
    border-radius: 7px;
    flex-shrink: 0;
    border: 1px solid rgba(0,0,0,.07);
  }
  .gv-mob-nophoto {
    display: flex !important;
    align-items: center; justify-content: center;
    background: #f1f5f9; font-size: 18px;
    border: 1px solid #e2e8f0;
  }
  .gv-name-text {
    flex: 1; min-width: 0;
    font-weight: 700; font-size: 13px; line-height: 1.35;
  }

  /* ── Group View — card layout ── */
  #gv-body td.mc-td-code,
  #gv-body td.mc-td-sz,
  #gv-body td.mc-td-stat { display: none !important; }

  #gv-body .gv-mob-extras { display: block !important; }

  #gv-body .gv-meta {
    display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap;
  }
  .gv-code { font-size: 11px; color: #64748b; font-weight: 400; }
  .gv-sz {
    font-size: 11px; background: #e2e8f0; border-radius: 4px;
    padding: 1px 7px; color: #475569; font-weight: 700;
  }

  #gv-body .gv-stats-row {
    display: flex !important;
    margin: 8px -13px -11px;
    background: #f8fafc;
    border-top: 1px solid #eff3f8;
    border-radius: 0 0 9px 9px;
    overflow: hidden;
  }
  .gv-stat {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 9px 4px;
    border-right: 1px solid #e9edf4;
    text-align: center;
  }
  .gv-stat:last-child { border-right: none; }
  .gv-sl {
    font-size: 9px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .3px; margin-bottom: 4px;
  }
  .gv-sv { font-size: 13px; font-weight: 600; line-height: 1.2; }
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
        @if(auth()->user()->role !== 'warehouse_operator')
        <div class="mod-actions">
            <button onclick="openInTransitSalesModal()" class="btn btn-info btn-sm">
                <i class="fa fa-list me-1"></i><span class="d-none d-sm-inline">ახალი გაყიდვები</span>
            </button>
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'purchases', 'can_create'))
            <button id="btn-new-purchase" onclick="openPurchaseModal()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი შესყიდვა</span>
            </button>
            @endif
        </div>
        @endif
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
        @if(auth()->user()->role !== 'warehouse_operator')
        <button class="pu-tab active" id="tab-btn-regular" onclick="switchPurchaseTab('regular')" type="button">
            <i class="fa fa-cart-shopping" style="font-size:12px;"></i> შესყიდვები
        </button>
        @endif
        <button class="pu-tab {{ auth()->user()->role === 'warehouse_operator' ? 'active' : '' }}" id="tab-btn-returns" onclick="switchPurchaseTab('returns')" type="button">
            <i class="fa fa-rotate-left" style="font-size:12px;"></i> დაბრუნება / გაცვლა
            @if($returnsInTransit > 0)
                <span class="tab-badge">{{ $returnsInTransit }}</span>
            @endif
        </button>
    </div>

    {{-- ── Tab Panel ── --}}
    <div class="pu-tab-panel">

        {{-- ══ ჩვეულებრივი შესყიდვები ══ --}}
        <div id="tab-regular" style="{{ auth()->user()->role === 'warehouse_operator' ? 'display:none;' : '' }}">
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
        <div id="tab-returns" style="{{ auth()->user()->role === 'warehouse_operator' ? '' : 'display:none;' }}">
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
                        <th>კლიენტი</th>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold">📋 ჯგუფის შემადგენლობა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive modal-card-table" id="gv-body"></div>
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
                <div class="table-responsive modal-card-table">
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
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
                <div class="table-responsive modal-card-table" id="in-transit-body" style="display:none;">
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
$.ajaxSetup({ cache: false });
$(function() {

    // ══ IMAGE ZOOM ══
    window.zoomPurchaseImg = function(el) {
        $('#zoom-img-src').attr('src', el.src);
        new bootstrap.Modal(document.getElementById('modal-img-zoom')).show();
    };

    var isWarehouseOperator = {{ auth()->user()->role === 'warehouse_operator' ? 'true' : 'false' }};
    var isAdmin             = {{ auth()->user()->role === 'admin' ? 'true' : 'false' }};

    function puProductCellRender(data, type, row) {
        if (type !== 'display') return data || '';
        // Desktop: original photo only
        var desktopHtml = '<div class="pu-cell-desktop">' + (data || '') + '</div>';
        // Mobile: composite card cell
        var groupItems = [];
        try { if (row.group_items_json) groupItems = JSON.parse(row.group_items_json); } catch(e) {}
        var isGroup = groupItems.length > 1;
        var leftHtml = isGroup
            ? '<div class="pu-group-badge"><i class="fa fa-layer-group"></i><span>' + groupItems.length + ' პ-ტი</span></div>'
            : (data ? '<div class="pu-prod-thumb">' + data + '</div>' : '');
        var name = row.product_name || '';
        var meta = [row.product_code, row.product_size, row.quantity ? ('×' + row.quantity) : ''].filter(Boolean).join(' · ');
        var price = (isAdmin && (row.payment || row.price_paid))
            ? '<div class="pu-prod-price">' + (row.payment || '') + (row.price_paid ? ' &nbsp; ' + row.price_paid : '') + '</div>'
            : '';
        var mobileHtml = '<div class="pu-cell-mobile"><div class="pu-product-cell">' + leftHtml +
            '<div class="pu-prod-info"><div class="pu-prod-name">' + name + '</div>' +
            (meta ? '<div class="pu-prod-meta">' + meta + '</div>' : '') + price + '</div></div></div>';
        return desktopHtml + mobileHtml;
    }

    // ══ TAB SWITCHING ══
    var currentTab = isWarehouseOperator ? 'returns' : 'regular';

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
        responsive: false,
        dom: 'rtip',
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: function(d) {
                d.type          = 'regular';
                d.status_filter = purchaseStatusFilter;
            }
        },
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2, className: 'pu-td-num' },
            { data: 'show_photo',      name: 'show_photo',      orderable: false, responsivePriority: 3, className: 'pu-td-product', render: puProductCellRender },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 1, orderable: false, className: 'pu-td-hide' },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9, className: 'pu-td-hide' },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 4, className: 'pu-td-hide' },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 5, className: 'pu-td-hide' },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7, visible: isAdmin, className: 'pu-td-hide' },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8, visible: isAdmin, className: 'pu-td-hide' },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 6, className: 'pu-td-status' },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 9, className: 'pu-td-date' },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2, className: 'pu-td-action' },
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
        $.ajax({ url: "{{ url('purchases/group') }}/" + groupId + "/items", cache: false, success: function(items) {
            items = items || [];

            var html = '<table class="table table-sm table-bordered mb-0">'
             + '<thead class="table-light"><tr>'
             + '<th style="width:52px"></th>'
             + '<th>პროდუქტი</th><th>კოდი</th><th>ზომა</th>'
             + '<th class="text-center">შეკვეთა</th>'
             + '<th class="text-center">გზაშია</th>'
             + '<th class="text-center">დაკარგ.</th>'
             + (isAdmin ? '<th class="text-end" style="color:#7c3aed;">თვიტ.($)</th>' : '')
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

                var gvStatsRow = '<div class="gv-stats-row">'
                    + '<div class="gv-stat"><div class="gv-sl">შეკვ.</div><div class="gv-sv">' + orig + '</div></div>'
                    + '<div class="gv-stat"><div class="gv-sl">გზაში</div><div class="gv-sv">' + remainCell + '</div></div>'
                    + '<div class="gv-stat"><div class="gv-sl">დაკარგ.</div><div class="gv-sv">' + lostCell + '</div></div>'
                    + (isAdmin ? '<div class="gv-stat"><div class="gv-sl">ღირ.</div><div class="gv-sv">' + costCell + '</div></div>' : '')
                    + '</div>';

                var imgThumb = it.product_image
                    ? '<img class="gv-mob-thumb" src="' + it.product_image + '">'
                    : '<span class="gv-mob-thumb gv-mob-nophoto">📦</span>';

                html += '<tr>'
                     +  '<td class="mc-td-img text-center align-middle">' + imgCell + '</td>'
                     +  '<td class="mc-td-name fw-semibold align-middle">'
                     +      '<span class="gv-desktop-name">' + (it.product_name||'N/A') + '</span>'
                     +      '<div class="gv-mob-extras">'
                     +          '<div class="gv-name-hdr">'
                     +              imgThumb
                     +              '<div class="gv-name-text">'
                     +                  (it.product_name||'N/A')
                     +                  '<div class="gv-meta">'
                     +                      (it.product_code ? '<span class="gv-code">' + it.product_code + '</span>' : '')
                     +                      (it.product_size ? '<span class="gv-sz">' + it.product_size + '</span>' : '')
                     +                  '</div>'
                     +              '</div>'
                     +          '</div>'
                     +          gvStatsRow
                     +      '</div>'
                     +  '</td>'
                     +  '<td class="mc-td-code text-muted align-middle" style="font-size:12px;">' + (it.product_code||'—') + '</td>'
                     +  '<td class="mc-td-sz align-middle">' + (it.product_size||'—') + '</td>'
                     +  '<td class="mc-td-stat text-center fw-bold align-middle">' + orig + '</td>'
                     +  '<td class="mc-td-stat text-center align-middle">' + remainCell + '</td>'
                     +  '<td class="mc-td-stat text-center align-middle">' + lostCell + '</td>'
                     +  (isAdmin ? '<td class="mc-td-stat text-end align-middle">' + costCell + '</td>' : '')
                     +  '</tr>';
            });

            html += '</tbody></table>';
            $('#gv-body').html(html);
            new bootstrap.Modal(document.getElementById('modal-group-view')).show();
        }});
    };

    // ══ RETURNS TABLE ══
    var returnsTable = $('#returns-table').DataTable({
        processing: true, serverSide: true,
        responsive: false,
        dom: 'rtip',
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: { type: 'returns' }
        },
        order: [[10, 'desc']],
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2, className: 'pu-td-num' },
            { data: 'customer_info',   name: 'customer_info',   orderable: false, responsivePriority: 1, className: 'pu-td-customer' },
            { data: 'show_photo',      name: 'show_photo',      orderable: false, responsivePriority: 5, className: 'pu-td-product', render: puProductCellRender },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 3, orderable: false, className: 'pu-td-hide' },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9, className: 'pu-td-hide' },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 4, className: 'pu-td-hide' },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 5, className: 'pu-td-hide' },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7, visible: isAdmin, className: 'pu-td-hide' },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8, visible: isAdmin, className: 'pu-td-hide' },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 6, className: 'pu-td-status' },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 9, className: 'pu-td-date' },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2, className: 'pu-td-action' },
            { data: 'is_return_purchase', visible: false },
            { data: 'group_items_json',   visible: false },
        ]
    });

    // გაყიდვებიდან ?tab=returns&search= პარამეტრი
    (function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('tab') === 'returns') {
            switchPurchaseTab('returns');
            var s = params.get('search');
            if (s) {
                $('#pu-search-returns').val(s);
                returnsTable.search(s).draw();
            }
            history.replaceState(null, '', window.location.pathname);
        }
    })();

    // ══ MULTI-LINE PURCHASE FORM ══
    var purchaseLineIndex    = 0;
    var isGroupEdit          = false;
    var isPurchaseLineInit   = false;   // suppresses change handler during addPurchaseLine init
    var productOptionsTpl    = document.getElementById('tpl-product-options').innerHTML;

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

        if (defaults && defaults.order_id) $tr.attr('data-order-id', defaults.order_id);

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
            isPurchaseLineInit = true;
            $prodSel.val(defaults.product_id || '').trigger('change.select2');
            isPurchaseLineInit = false;

            var opt         = $prodSel.find(':selected');
            var sizes       = (opt.attr('data-sizes') || '').toString();
            var isDivisible = opt.attr('data-divisible') === '1';
            var szName      = $sizeSel.attr('name') || '';

            if (isDivisible) {
                var $mlInput = $('<input type="number" required>')
                    .addClass('form-control form-control-sm line-size')
                    .attr({ name: szName, min: 1, step: 1, placeholder: 'მლ' })
                    .val(defaults.product_size || '');
                $sizeSel.replaceWith($mlInput);
            } else {
                // rebuild size select with all sizes, pre-selected
                var $sz = $('<select required>')
                    .addClass('form-select form-select-sm line-size')
                    .attr('name', szName)
                    .append('<option value="">— ზომა —</option>');
                if (sizes) {
                    sizes.split(',').forEach(function(s) {
                        s = s.trim();
                        if (s) $sz.append('<option value="' + s + '">' + s + '</option>');
                    });
                }
                $sizeSel.replaceWith($sz);
                $sz.val(defaults.product_size || '');
            }

            $qty.val(defaults.quantity != null ? defaults.quantity : 1);
            $priceUsa.val(defaults.price_usa || '');
            if (defaults.transport != null && defaults.transport !== '' && defaults.transport !== undefined) {
                $transport.val(defaults.transport);
            }
            $priceGeo.val(defaults.price_georgia ? parseFloat(defaults.price_georgia).toFixed(2) : '');

            if (defaults.is_fully_received) {
                // საწყობში მიღებული: ნამდვილი qty ჩანს, min = courier_count
                $qty.attr('min', defaults.courier_count || 1);
                var $check = $('<span style="color:#16a34a;font-weight:800;font-size:15px;margin-left:5px;white-space:nowrap;" title="საწყობში მიღებულია">✓</span>');
                var $wrap = $('<div style="display:flex;align-items:center;"></div>');
                $qty.wrap($wrap);
                $qty.after($check);
                $tr.find('.line-product, .line-size, .line-price-usa, .line-transport')
                   .prop('disabled', true).css('background', '#d1fae5');
                $tr.find('td').css('background', '#d1fae5');
                $tr.css('border-left', '3px solid #16a34a');
            } else if (defaults.locked) {
                $tr.find('.line-product, .line-size, .line-price-usa, .line-transport')
                   .prop('disabled', true).css('background', '#f5f5f5');
                $tr.find('.line-qty').attr('min', defaults.courier_count || 1).css('background', '#fff8e1');
            }
        }

        updateRemoveButtons();
    };

    function updateRemoveButtons() {
        var $rows = $('#purchase-lines-body .purchase-line');
        $rows.find('.remove-line').toggle($rows.length > 1);
    }

    $(document).on('change', '#purchase-lines-body .line-product', function() {
        if (isPurchaseLineInit) return;
        var $tr         = $(this).closest('tr');
        var prodId      = $(this).val();
        var opt         = $(this).find(':selected');
        var sizes       = (opt.attr('data-sizes') || '').toString();
        var geo         = opt.attr('data-price-ge') || 0;
        var isDivisible = opt.attr('data-divisible') === '1';
        var $oldSz      = $tr.find('.line-size');
        var szName      = $oldSz.attr('name') || '';

        if (isDivisible) {
            var $mlIn = $('<input type="number" required>')
                .addClass('form-control form-control-sm line-size')
                .attr({ name: szName, min: 1, step: 1, placeholder: 'მლ' });
            $oldSz.replaceWith($mlIn);
        } else {
            var $sz = $('<select required>')
                .addClass('form-select form-select-sm line-size')
                .attr('name', szName)
                .append('<option value="">—</option>');
            if (sizes) {
                sizes.split(',').forEach(function(s) {
                    s = s.trim();
                    if (s) $sz.append('<option value="' + s + '">' + s + '</option>');
                });
            }
            $oldSz.replaceWith($sz);
        }

        $tr.find('.line-price-geo').val(geo ? parseFloat(geo).toFixed(2) : '');
        $tr.find('.line-fifo').text('');

        // იგივე პროდუქტის სხვა row-ში ტრანსპ. თუ უკვე შეყვანილია — ამ row-შიც შეავსე
        if (prodId) {
            $('#purchase-lines-body .purchase-line').not($tr).each(function() {
                if ($(this).find('.line-product').val() === prodId) {
                    var t = parseFloat($(this).find('.line-transport').val()) || 0;
                    if (t > 0) { $tr.find('.line-transport').val(t); return false; }
                }
            });
        }
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

    // ── ტრანსპ. სინქრონიზაცია — მხოლოდ იმავე პროდუქტის row-ებზე ──
    $(document).on('input', '#purchase-lines-body .line-transport', function() {
        if (isGroupEdit) return;
        var val     = $(this).val();
        var prodId  = $(this).closest('tr').find('.line-product').val();
        $('#purchase-lines-body .purchase-line').not($(this).closest('tr')).each(function() {
            if ($(this).find('.line-product').val() === prodId) {
                $(this).find('.line-transport').val(val);
            }
        });
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
            isGroupEdit = !!data.is_group;
            $('#purchase_id').val(data.id);
            $('input[name="_method"]', '#form-purchase').val('PATCH');
            $('#purchase-modal-title').text('✏️ ' + (data.order_number || '#' + data.id));
            $('#purchase-lines-body').empty();
            $('#btn-add-line').hide();
            $('#purchase_comment').val(data.comment || '');
            $('#purchase_courier_section').hide();
            $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);

            if (data.is_group) {
                // ── ჯგუფური რედაქტირება ──────────────────────────────
                var anyLocked = false;
                data.items.forEach(function(item) {
                    var locked = (item.courier_count || 0) > 0 && !item.is_fully_received;
                    if (locked) anyLocked = true;
                    addPurchaseLine({
                        order_id:          item.id,
                        product_id:        item.product_id,
                        product_size:      item.product_size,
                        quantity:          item.quantity,
                        price_usa:         item.price_usa,
                        transport:         item.courier_price_international || 0,
                        price_georgia:     item.price_georgia || 0,
                        courier_count:     item.courier_count || 0,
                        locked:            locked,
                        is_fully_received: item.is_fully_received || false,
                    });
                });
                if (anyLocked) {
                    $('#purchase-courier-lock-msg').remove();
                    var lockMsg = '<div id="purchase-courier-lock-msg" style="background:#fff3cd;border:1px solid #ffc107;' +
                        'border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12px;color:#856404;">' +
                        '⚠️ ზოგიერთ ჩანაწერს გაყიდვა უკვე განხორციელდა — პროდუქტი/ზომა/ფასი/ტრანსპ. ვერ შეიცვლება.</div>';
                    $('#form-purchase .modal-body').prepend(lockMsg);
                }
            } else {
                // ── ერთი ჩანაწერის რედაქტირება ──────────────────────
                if (data.is_return_purchase) {
                    $('#purchase_courier_section').show();
                    var cType = 'none';
                    if ((data.courier_price_tbilisi || 0) > 0) cType = 'tbilisi';
                    else if ((data.courier_price_region  || 0) > 0) cType = 'region';
                    else if ((data.courier_price_village || 0) > 0) cType = 'village';
                    $('input[name="purchase_courier_type"][value="' + cType + '"]').prop('checked', true);
                }

                addPurchaseLine({
                    product_id:         data.product_id,
                    product_size:       data.product_size,
                    quantity:           data.quantity,
                    price_usa:          data.price_usa,
                    transport:          data.is_return_purchase ? 0 : (data.courier_price_international || 0),
                    price_georgia:      data.price_georgia || 0,
                    is_fully_received:  data.is_fully_received || false,
                    received_qty:       data.received_qty || 0,
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
            }

            $('#modal-purchase').modal('show');
        });
    };

    $('#modal-purchase').on('hidden.bs.modal', function() {
        isGroupEdit = false;
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
        var $saveBtn = $('#btn-purchase-save');
        if ($saveBtn.prop('disabled')) return;

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

        $saveBtn.prop('disabled', true).css('opacity', '0.65');
        var $locked = $(this).find(':disabled').not($saveBtn).prop('disabled', false);
        var comment = $('#purchase_comment').val();
        var formData;

        if (isGroupEdit) {
            // ── ჯგუფური განახლება: თითოეულ ჩანაწერს ინდივიდუალურად ვაგზავნით ──
            var groupRows = [];
            $('#purchase-lines-body .purchase-line').each(function() {
                var $r = $(this);
                groupRows.push({
                    orderId:   $r.data('order-id'),
                    data: {
                        _method:                     'PATCH',
                        _token:                      "{{ csrf_token() }}",
                        order_type:                  'purchase',
                        product_id:                  $r.find('.line-product').val(),
                        product_size:                $r.find('.line-size').val(),
                        quantity:                    $r.find('.line-qty').val(),
                        price_usa:                   $r.find('.line-price-usa').val() || 0,
                        courier_price_international: $r.find('.line-transport').val() || 0,
                        price_georgia:               $r.find('.line-price-geo').val() || 0,
                        comment:                     comment,
                    }
                });
            });

            $locked.prop('disabled', true);

            function sendNext(idx) {
                if (idx >= groupRows.length) {
                    $saveBtn.prop('disabled', false).css('opacity', '');
                    $('#modal-purchase').modal('hide');
                    purchasesTable.ajax.reload();
                    returnsTable.ajax.reload();
                    refreshPurchaseStats();
                    swal({ title: '✅', text: 'ჯგუფი განახლდა!', type: 'success', timer: 1800 });
                    return;
                }
                var row = groupRows[idx];
                $.ajax({
                    url: "{{ url('purchases') }}/" + row.orderId,
                    type: 'POST', data: row.data,
                    success: function() { sendNext(idx + 1); },
                    error: function(xhr) {
                        $saveBtn.prop('disabled', false).css('opacity', '');
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                        swal({ title: 'შეცდომა', text: msg, type: 'error' });
                    }
                });
            }
            sendNext(0);
            return;
        }

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
                comment:                     comment,
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
            },
            complete: function() {
                $saveBtn.prop('disabled', false).css('opacity', '');
            }
        });
    });

    // ══ GROUP RECEIVE ══
    window.openGroupReceive = function(groupId) {
        $('#gr-lines-body').empty();
        $('#gr-group-id').val(groupId);
        $('#btn-gr-save').prop('disabled', false);

        $.ajax({ url: "{{ url('purchases/group') }}/" + groupId + "/items", cache: false, success: function(allItems) {
            var inTransit = (allItems || []).filter(function(it) { return it.status_id === 2; });
            if (!inTransit.length) {
                swal('ინფო', 'ამ ჯგუფში სტატუს=2 ორდერი არ მოიძებნა', 'info');
                return;
            }
            (allItems || []).forEach(function(it) {
                var $imgCell = $('<td class="mc-td-img text-center align-middle" style="width:52px;">');
                if (it.product_image) {
                    $imgCell.append($('<img>').attr('src', it.product_image)
                        .css({ width: '44px', height: '44px', objectFit: 'cover', borderRadius: '4px' }));
                } else {
                    $imgCell.text('📦');
                }

                if (it.status_id === 3) {
                    // Already received — image + code + size + badge in name cell for mobile
                    var $mobMeta3 = $('<div class="gv-meta">');
                    if (it.product_code) $mobMeta3.append($('<span class="gv-code">').text(it.product_code));
                    if (it.product_size) $mobMeta3.append($('<span class="gv-sz">').text(it.product_size));
                    $mobMeta3.append($('<span class="badge bg-success ms-1" style="font-size:11px;font-weight:600;">').text('✅ მიღებულია'));
                    var $thumb3 = it.product_image
                        ? $('<img class="gv-mob-thumb">').attr('src', it.product_image)
                        : $('<span class="gv-mob-thumb gv-mob-nophoto">').text('📦');
                    var $nameHdr3 = $('<div class="gv-name-hdr">').append(
                        $thumb3,
                        $('<div class="gv-name-text">').append(document.createTextNode(it.product_name)).append($mobMeta3)
                    );
                    var $nameCell3 = $('<td class="mc-td-name fw-semibold align-middle text-muted">')
                        .append($('<span class="gv-desktop-name">').text(it.product_name))
                        .append($('<div class="gv-mob-extras">').append($nameHdr3));

                    var $tr = $('<tr class="table-success opacity-75">').append(
                        $imgCell,
                        $nameCell3,
                        $('<td class="mc-td-sz align-middle text-muted">').attr('data-label', 'ზომა').text(it.product_size || '—'),
                        $('<td class="text-center text-muted gr-ordered">').attr('data-label', 'შეკვ.').text(it.quantity),
                        $('<td colspan="2" class="text-center">').attr('data-label', 'სტ.').html(
                            '<span class="badge bg-success" style="font-size:12px;">✅ მიღებულია</span>'
                        )
                    );
                    $('#gr-lines-body').append($tr);

                } else {
                    // Pending — image + code + size in name header, stat cells in horizontal row
                    var $mobMeta = $('<div class="gv-meta">');
                    if (it.product_code) $mobMeta.append($('<span class="gv-code">').text(it.product_code));
                    if (it.product_size) $mobMeta.append($('<span class="gv-sz">').text(it.product_size));
                    var $thumb = it.product_image
                        ? $('<img class="gv-mob-thumb">').attr('src', it.product_image)
                        : $('<span class="gv-mob-thumb gv-mob-nophoto">').text('📦');
                    var $nameHdr = $('<div class="gv-name-hdr">').append(
                        $thumb,
                        $('<div class="gv-name-text">').append(document.createTextNode(it.product_name)).append($mobMeta)
                    );
                    var $nameCell = $('<td class="mc-td-name fw-semibold align-middle">')
                        .append($('<span class="gv-desktop-name">').text(it.product_name))
                        .append($('<div class="gv-mob-extras">').append($nameHdr));

                    var $tr = $('<tr data-order-id="' + it.id + '">').append(
                        $imgCell,
                        $nameCell,
                        $('<td class="mc-td-sz align-middle">').attr('data-label', 'ზომა').text(it.product_size || '—'),
                        $('<td class="text-center fw-bold text-muted gr-ordered gr-stat">').attr('data-label', 'შეკვ.').text(it.quantity),
                        $('<td class="gr-stat">').attr('data-label', '✅ მიღ.').append(
                            $('<input type="number" class="form-control form-control-sm text-center gr-received">')
                                .val(it.quantity).attr({ min: 0, max: it.quantity })
                        ),
                        $('<td class="gr-stat">').attr('data-label', '❌ დაკარგ.').append(
                            $('<input type="number" class="form-control form-control-sm text-center gr-lost">')
                                .val(0).attr({ min: 0, max: it.quantity })
                        )
                    );
                    $('#gr-lines-body').append($tr);
                }
            });
            new bootstrap.Modal(document.getElementById('modal-group-receive')).show();
        }});
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

        $('#gr-lines-body tr[data-order-id]').each(function() {
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

            items.forEach(function(it, idx) {
                var img = it.image_url
                    ? '<img src="' + it.image_url + '" style="width:44px;height:44px;object-fit:cover;border-radius:4px;cursor:zoom-in;" onclick="zoomPurchaseImg(this)">'
                    : '<span class="text-muted">—</span>';
                var price = it.price_geo ? parseFloat(it.price_geo).toFixed(2) + ' ₾' : '—';
                var sizeCell;
                if (it.is_divisible) {
                    sizeCell = '<input type="number" class="form-control form-control-sm transit-ml-input" '
                             + 'data-idx="' + idx + '" value="' + (it.product_size || '') + '" '
                             + 'min="1" step="1" style="width:70px;" placeholder="მლ">';
                } else {
                    sizeCell = (it.product_size || '—');
                }
                $('#in-transit-rows').append(
                    '<tr>'
                    + '<td class="mc-td-img text-center">' + img + '</td>'
                    + '<td class="mc-td-name fw-semibold">' + $('<span>').text(it.product_name).html() + '</td>'
                    + '<td class="text-muted" data-label="კოდი">' + $('<span>').text(it.product_code).html() + '</td>'
                    + '<td class="text-center" data-label="ზომა">' + sizeCell + '</td>'
                    + '<td class="text-center fw-bold" data-label="რაოდ.">' + it.quantity + '</td>'
                    + '<td class="text-end" data-label="ფასი">' + price + '</td>'
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

        inTransitItems.forEach(function(it, idx) {
            var size = it.product_size;
            if (it.is_divisible) {
                var mlVal = parseFloat($('.transit-ml-input[data-idx="' + idx + '"]').val()) || 0;
                if (mlVal > 0) size = mlVal.toString();
            }
            addPurchaseLine({
                product_id:    it.product_id,
                product_size:  size,
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

    // warehouse_operator-სთვის auto-switch returns tab-ზე
    if (isWarehouseOperator) {
        switchPurchaseTab('returns');
    }

});
</script>
@endsection