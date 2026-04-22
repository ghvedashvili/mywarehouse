@extends('layouts.master')
@section('page_title')<i class="fa fa-right-from-bracket me-2" style="color:#e74c3c;"></i>გაყიდვები@endsection

@section('top')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
/* ── Select2 ── */
.select2-container--default .select2-selection--single { height:34px; border:1px solid #e8eaed; border-radius:8px; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height:34px; padding-left:10px; color:#111827; font-size:13px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:34px; }
/* ── Nested modal ── */
#modal-form { z-index:1060; }
#modal-form + .modal-backdrop { z-index:1055; }
/* ── Bootstrap 3 compat box ── */
.box { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.05); margin-bottom:20px; }
.box-title { font-size:15px; font-weight:600; margin:0; }
table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control::before { background-color:#2563eb; border-radius:50%; }

/* ═══════════════════════════════════════════════
   PAGE TOKENS
═══════════════════════════════════════════════ */
.po-page {
  --po-bg:          #f4f5f7;
  --po-surface:     #ffffff;
  --po-surface2:    #f9fafb;
  --po-border:      #e8eaed;
  --po-border-soft: #f0f1f3;
  --po-text-1:      #111827;
  --po-text-2:      #4b5563;
  --po-text-3:      #9ca3af;
  --po-accent:      #2563eb;
  --po-accent-soft: #eff4ff;
  --po-green:       #16a34a;
  --po-green-soft:  #f0fdf4;
  --po-red:         #dc2626;
  --po-red-soft:    #fef2f2;
  --po-amber:       #d97706;
  --po-amber-soft:  #fffbeb;
  --po-purple:      #7c3aed;
  --po-purple-soft: #f5f3ff;
  --po-teal:        #0d9488;
  --po-teal-soft:   #f0fdfa;
  --po-radius:      12px;
  --po-radius-sm:   8px;
  --po-trans:       .18s cubic-bezier(.4,0,.2,1);
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
}
.po-page.po-dark {
  --po-bg:          #0f1117;
  --po-surface:     #1a1d27;
  --po-surface2:    #22263a;
  --po-border:      #2a2e42;
  --po-border-soft: #232740;
  --po-text-1:      #f1f5f9;
  --po-text-2:      #94a3b8;
  --po-text-3:      #475569;
  --po-accent:      #3b82f6;
  --po-accent-soft: #1e2d4a;
  --po-green-soft:  #0d2d1a;
  --po-red-soft:    #2d1010;
  --po-amber-soft:  #2d2010;
  --po-purple-soft: #1e1030;
  --po-teal-soft:   #0d2d2a;
  background: var(--po-bg) !important;
  color: var(--po-text-1);
}

/* ═══════════════════════════════════════════════
   STATS STRIP
═══════════════════════════════════════════════ */
.po-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:16px; }
.po-stat {
  background:var(--po-surface); border:1px solid var(--po-border);
  border-radius:var(--po-radius); padding:16px 18px;
  box-shadow:0 1px 3px rgba(0,0,0,.06);
  transition:box-shadow var(--po-trans), transform var(--po-trans), background var(--po-trans);
}
.po-stat:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); transform:translateY(-1px); }
.po-stat-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; margin-bottom:10px; }
.po-stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--po-text-3); margin-bottom:5px; }
.po-stat-value { font-size:22px; font-weight:700; letter-spacing:-.5px; color:var(--po-text-1); }
.po-stat-sub { font-size:11px; color:var(--po-text-3); margin-top:4px; }
@media(max-width:1100px){ .po-stats { grid-template-columns:repeat(2,1fr); } }
@media(max-width:600px)  { .po-stats { grid-template-columns:repeat(2,1fr); gap:10px; } .po-stat-value { font-size:18px; } }

/* ═══════════════════════════════════════════════
   FILTER BAR
═══════════════════════════════════════════════ */
.po-filter-bar {
  background:var(--po-surface); border:1px solid var(--po-border);
  border-radius:var(--po-radius); padding:12px 16px;
  display:flex; align-items:center; flex-wrap:wrap; gap:10px;
  box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:16px;
  transition:background var(--po-trans);
}
.po-search {
  display:flex; align-items:center; gap:8px;
  background:var(--po-bg); border:1px solid var(--po-border);
  border-radius:var(--po-radius-sm); padding:7px 12px;
  flex:1; min-width:160px; max-width:260px;
  transition:border-color var(--po-trans);
}
.po-search:focus-within { border-color:var(--po-accent); }
.po-search input { background:none; border:none; outline:none; color:var(--po-text-1); font-size:13px; width:100%; font-family:inherit; }
.po-search input::placeholder { color:var(--po-text-3); }
.po-search i { color:var(--po-text-3); font-size:12px; flex-shrink:0; }
.po-pill-group { display:flex; gap:4px; flex-wrap:wrap; }
.po-pill {
  padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600;
  border:1.5px solid var(--po-border); background:var(--po-surface);
  color:var(--po-text-2); cursor:pointer; transition:all var(--po-trans); white-space:nowrap;
}
.po-pill:hover { border-color:var(--po-accent); color:var(--po-accent); }
.po-pill.active { background:var(--po-accent); border-color:var(--po-accent); color:#fff; }
.po-filter-sep { width:1px; height:28px; background:var(--po-border); flex-shrink:0; }
@media(max-width:768px){ .po-filter-sep { display:none; } }
.po-select {
  background:var(--po-bg); border:1px solid var(--po-border);
  border-radius:var(--po-radius-sm); color:var(--po-text-1);
  font-size:12.5px; padding:6px 10px; outline:none;
  cursor:pointer; transition:border-color var(--po-trans); font-family:inherit;
}
.po-select:focus { border-color:var(--po-accent); }
.po-toggle-wrap { display:flex; align-items:center; gap:7px; }
.po-toggle-wrap label { font-size:12px; font-weight:500; color:var(--po-text-2); cursor:pointer; white-space:nowrap; }
/* custom dates */
.po-custom-dates { display:none; align-items:center; gap:5px; }
.po-custom-dates.show { display:flex; }
.po-custom-dates input[type=date] {
  border:1.5px solid var(--po-border); border-radius:var(--po-radius-sm);
  padding:4px 8px; font-size:12px; color:var(--po-text-1); width:120px;
  background:var(--po-bg); outline:none; font-family:inherit;
}
.po-apply-btn {
  background:var(--po-accent); color:#fff; border:none; border-radius:var(--po-radius-sm);
  padding:4px 10px; font-size:12px; font-weight:600; cursor:pointer; font-family:inherit;
}
/* status dropdown */
#po-status-dropdown {
  display:none; position:absolute; top:calc(100% + 4px); left:0; z-index:9999;
  background:var(--po-surface); border:1.5px solid var(--po-border);
  border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.12); min-width:190px; padding:8px 0;
}
#po-status-dropdown label {
  display:flex; align-items:center; gap:9px; padding:6px 14px; cursor:pointer;
  font-size:12.5px; font-weight:500; color:var(--po-text-2); margin:0;
  transition:background var(--po-trans);
}
#po-status-dropdown label:hover { background:var(--po-bg); }
#po-status-dropdown input[type=checkbox] { accent-color:var(--po-accent); width:14px; height:14px; }

/* ═══════════════════════════════════════════════
   BULK BAR
═══════════════════════════════════════════════ */
.po-bulk-bar {
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  background:var(--po-accent); color:#fff;
  padding:8px 16px; border-radius:var(--po-radius-sm);
  font-size:13px; font-weight:600; margin-bottom:16px;
  animation:po-slidedown .2s ease-out;
}
@keyframes po-slidedown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
.po-bulk-btn {
  padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700;
  background:rgba(255,255,255,.2); color:#fff; cursor:pointer;
  display:flex; align-items:center; gap:6px; border:none; font-family:inherit;
  transition:background var(--po-trans);
}
.po-bulk-btn:hover { background:rgba(255,255,255,.3); }

/* ═══════════════════════════════════════════════
   TABLE CARD
═══════════════════════════════════════════════ */
.po-table-card {
  background:var(--po-surface); border:1px solid var(--po-border);
  border-radius:var(--po-radius); box-shadow:0 1px 3px rgba(0,0,0,.06);
  overflow:hidden; transition:background var(--po-trans);
}
#products-out-table { border-collapse:collapse !important; width:100%; }
#products-out-table thead tr { background:var(--po-surface2); }
#products-out-table thead th {
  padding:10px 14px !important; text-align:left;
  font-size:11px !important; font-weight:700 !important; text-transform:uppercase !important;
  letter-spacing:.6px !important; color:var(--po-text-3) !important;
  border-bottom:1px solid var(--po-border) !important; white-space:nowrap;
}
#products-out-table tbody td {
  padding:13px 14px !important; vertical-align:middle !important;
  border-bottom:1px solid var(--po-border-soft) !important; font-size:13px;
  color:var(--po-text-1);
}
#products-out-table tbody tr { transition:background var(--po-trans); cursor:pointer; }
/* subtle row separation — every non-child row gets a slightly thicker bottom border */
#products-out-table tbody tr:not(.po-child-row) td {
  border-bottom:2px solid var(--po-border) !important;
}
#products-out-table tbody tr:last-child td { border-bottom:none !important; }
#products-out-table tbody tr:hover td { background:var(--po-bg) !important; }
/* Row states */
#products-out-table tbody tr.po-row-debt   td:first-child { box-shadow:inset 3px 0 0 var(--po-red); }
#products-out-table tbody tr.po-row-change td:first-child { box-shadow:inset 3px 0 0 var(--po-accent); }
#products-out-table tbody tr.po-row-returned td { opacity:.72; }
#products-out-table tbody tr.po-row-exchanged td { background:color-mix(in srgb,var(--po-purple-soft) 60%,transparent) !important; }
#products-out-table tbody tr.po-group-row td { background:var(--po-surface2) !important; border-top:2px solid var(--po-border) !important; }
#products-out-table tbody tr.po-child-row { animation:po-expandrow .2s ease-out; }
#products-out-table tbody tr.po-child-row td { background:color-mix(in srgb,var(--po-accent-soft) 40%,transparent) !important; }
@keyframes po-expandrow { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }

/* ═══════════════════════════════════════════════
   TABLE CELL COMPONENTS
═══════════════════════════════════════════════ */
/* Status labels (Bootstrap 3 compat — server-rendered) */
.label {
  display:inline-flex !important; align-items:center !important; gap:4px !important;
  padding:3px 9px !important; border-radius:20px !important;
  font-size:11px !important; font-weight:600 !important; white-space:nowrap !important;
  line-height:1.3 !important;
}
.label-default  { background:var(--po-surface2,#f9fafb) !important; color:var(--po-text-3,#9ca3af) !important; }
.label-primary  { background:var(--po-accent-soft,#eff4ff) !important; color:var(--po-accent,#2563eb) !important; }
.label-success  { background:var(--po-green-soft,#f0fdf4) !important; color:var(--po-green,#16a34a) !important; }
.label-info     { background:var(--po-teal-soft,#f0fdfa) !important; color:var(--po-teal,#0d9488) !important; }
.label-warning  { background:var(--po-amber-soft,#fffbeb) !important; color:var(--po-amber,#d97706) !important; }
.label-danger   { background:var(--po-red-soft,#fef2f2) !important; color:var(--po-red,#dc2626) !important; }
.label-purple   { background:var(--po-purple-soft,#f5f3ff) !important; color:var(--po-purple,#7c3aed) !important; }

/* Order number badge */
.po-order-num {
  font-family:'DM Mono','Cascadia Code','SF Mono',monospace;
  font-size:12px; font-weight:500; color:var(--po-text-2);
  background:var(--po-bg); padding:2px 7px;
  border-radius:5px; border:1px solid var(--po-border); white-space:nowrap;
  display:inline-block;
}
.po-order-num.group { color:var(--po-purple); border-color:var(--po-purple); background:var(--po-purple-soft); }

/* Courier tag */
.po-courier-tag {
  display:inline-flex; align-items:center; gap:4px;
  font-size:10px; font-weight:600; color:var(--po-accent);
  background:var(--po-accent-soft); border-radius:4px; padding:2px 6px;
}
/* Merge hint */
.po-merge-hint {
  color:var(--po-amber); font-size:10px; cursor:pointer;
  opacity:.65; transition:opacity var(--po-trans); margin-left:2px;
}
.po-merge-hint:hover { opacity:1; }
/* Expand button */
.po-expand-btn {
  display:inline-flex; align-items:center; gap:4px;
  padding:3px 8px; border-radius:5px;
  background:var(--po-bg); border:1px solid var(--po-border);
  color:var(--po-text-2); font-size:11px; font-weight:600;
  cursor:pointer; transition:all var(--po-trans); user-select:none;
  margin-top:5px;
}
.po-expand-btn:hover { background:var(--po-border); }
.po-expand-btn.open i { transform:rotate(90deg); }
.po-expand-btn i { transition:transform var(--po-trans); }
.po-group-badges { display:flex; flex-direction:column; gap:3px; margin-top:4px; }

/* Product cell */
.po-product-cell { display:flex; align-items:center; gap:10px; }
.po-product-thumb {
  width:40px; height:40px; border-radius:8px; flex-shrink:0; overflow:hidden;
  background:var(--po-bg); border:1px solid var(--po-border);
  cursor:zoom-in; transition:transform .15s;
}
.po-product-thumb:hover { transform:scale(1.08); }
.po-product-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

/* Finance cell */
.po-finance-total { font-size:14px; font-weight:700; letter-spacing:-.3px; color:var(--po-text-1); }
.po-paid-row { display:flex; align-items:center; gap:6px; margin-top:3px; }
.po-paid-bar { height:3px; border-radius:3px; background:var(--po-border); flex:1; max-width:60px; overflow:hidden; }
.po-paid-fill { height:100%; border-radius:3px; }
.po-paid-tag { display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:700; color:var(--po-green); background:var(--po-green-soft); border-radius:20px; padding:2px 8px; }
.po-debt-tag { display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:700; color:var(--po-red); background:var(--po-red-soft); border-radius:20px; padding:2px 8px; }

/* Date cell */
.po-date-cell { font-size:12px; color:var(--po-text-2); white-space:nowrap; }
.po-date-cell small { display:block; font-size:10px; color:var(--po-text-3); }

/* Action buttons */
.po-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }
.btn-xs {
  width:28px !important; height:28px !important;
  display:inline-flex !important; align-items:center !important; justify-content:center !important;
  padding:0 !important; border-radius:6px !important;
  font-size:11px !important; line-height:1 !important;
  border:1px solid transparent !important;
  transition:all var(--po-trans,.18s) !important; cursor:pointer;
}
.btn-xs:hover { transform:scale(1.1); box-shadow:0 2px 8px rgba(0,0,0,.18) !important; }
.btn-xs.btn-warning  { background:var(--po-amber-soft,#fffbeb) !important; color:var(--po-amber,#d97706) !important; border-color:color-mix(in srgb,#d97706 25%,transparent) !important; }
.btn-xs.btn-warning:hover  { background:var(--po-amber,#d97706) !important; color:#fff !important; }
.btn-xs.btn-danger   { background:var(--po-red-soft,#fef2f2) !important; color:var(--po-red,#dc2626) !important; border-color:color-mix(in srgb,#dc2626 25%,transparent) !important; }
.btn-xs.btn-danger:hover   { background:var(--po-red,#dc2626) !important; color:#fff !important; }
.btn-xs.btn-success  { background:var(--po-green-soft,#f0fdf4) !important; color:var(--po-green,#16a34a) !important; border-color:color-mix(in srgb,#16a34a 25%,transparent) !important; }
.btn-xs.btn-success:hover  { background:var(--po-green,#16a34a) !important; color:#fff !important; }
.btn-xs.btn-info     { background:var(--po-teal-soft,#f0fdfa) !important; color:var(--po-teal,#0d9488) !important; border-color:color-mix(in srgb,#0d9488 25%,transparent) !important; }
.btn-xs.btn-info:hover     { background:var(--po-teal,#0d9488) !important; color:#fff !important; }
.btn-xs.btn-primary  { background:var(--po-accent-soft,#eff4ff) !important; color:var(--po-accent,#2563eb) !important; border-color:color-mix(in srgb,#2563eb 25%,transparent) !important; }
.btn-xs.btn-primary:hover  { background:var(--po-accent,#2563eb) !important; color:#fff !important; }
.btn-xs.btn-default, .btn-xs.btn-secondary { background:var(--po-surface2,#f9fafb) !important; color:var(--po-text-2,#4b5563) !important; border-color:var(--po-border,#e8eaed) !important; }
.btn-xs.btn-default:hover, .btn-xs.btn-secondary:hover { background:var(--po-border,#e8eaed) !important; }
/* Pay button states */
.po-pay-paid    { background:var(--po-green,#16a34a) !important; color:#fff !important; border-color:var(--po-green,#16a34a) !important; }
.po-pay-debt    { background:var(--po-red,#dc2626)   !important; color:#fff !important; border-color:var(--po-red,#dc2626)   !important; }
.po-pay-partial { background:var(--po-amber,#d97706) !important; color:#fff !important; border-color:var(--po-amber,#d97706) !important; }
/* Send-to-courier button is text+icon, lives in Col 1 (not .po-actions) — needs auto width */
.po-page td:nth-child(2) .btn-xs, .po-page td:nth-child(2) span.btn-xs {
  width:auto !important; padding:3px 10px !important; gap:5px; margin-top:3px; display:inline-flex !important;
}

/* ═══════════════════════════════════════════════
   PAGINATION
═══════════════════════════════════════════════ */
.dataTables_wrapper .dataTables_paginate .paginate_button {
  padding:5px 10px !important; font-size:12px !important; border-radius:6px !important;
  margin:0 2px !important; border:1px solid var(--po-border,#e8eaed) !important;
  background:var(--po-surface,#fff) !important; color:var(--po-text-2,#4b5563) !important;
  font-weight:500 !important; transition:all .15s !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
  background:var(--po-accent,#2563eb) !important; color:#fff !important;
  border-color:var(--po-accent,#2563eb) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
  background:var(--po-bg,#f4f5f7) !important; color:var(--po-text-1,#111827) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled { color:var(--po-text-3,#9ca3af) !important; }
.dataTables_wrapper .dataTables_info { font-size:12px; color:var(--po-text-3,#9ca3af); }

/* ═══════════════════════════════════════════════
   HEADER EXTRAS
═══════════════════════════════════════════════ */
.po-theme-btn {
  width:32px; height:32px; border-radius:8px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  background:var(--po-bg,#f4f5f7); border:1px solid var(--po-border,#e8eaed);
  color:var(--po-text-2,#4b5563); cursor:pointer; font-size:14px;
  transition:all var(--po-trans); margin:0;
}
.po-theme-btn:hover { background:var(--po-border,#e8eaed); }
.po-drop-item {
  display:flex; align-items:center; gap:8px;
  padding:9px 14px; font-size:13px; color:var(--po-text-2,#4b5563);
  cursor:pointer; transition:background var(--po-trans); text-decoration:none;
}
.po-drop-item:hover { background:var(--po-bg,#f4f5f7); color:var(--po-text-1,#111827); }
.po-drop-item i { width:16px; text-align:center; font-size:12px; }

/* ── Checkboxes — hidden by default, shown only in merge mode ── */
#check-all, .row-check { accent-color:var(--po-accent,#2563eb); cursor:pointer; }
.po-page:not(.po-merge-mode) #products-out-table th:nth-child(1),
.po-page:not(.po-merge-mode) #products-out-table td:nth-child(1) {
  width:0 !important; min-width:0 !important; padding:0 !important;
  overflow:hidden; border:none !important;
}
.po-page:not(.po-merge-mode) #check-all,
.po-page:not(.po-merge-mode) .row-check { display:none !important; }

/* ── Mobile ── */
@media(max-width:640px) {
  .po-product-thumb { display:none; }
  .po-filter-sep { display:none; }
  #products-out-table th:nth-child(6),
  #products-out-table td:nth-child(6) { display:none; }
}
</style>
@endsection

@section('content')
<div class="mod-wrap po-page" id="po-page-root">

    {{-- ── HEADER ── --}}
    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-right-from-bracket me-2" style="color:var(--po-red,#dc2626);font-size:16px;"></i>გაყიდვები</h2>
            <p class="mod-subtitle">გაყიდვის ორდერების მართვა</p>
        </div>
        <div class="mod-actions" style="align-items:center;">
            <button onclick="addSaleForm()" class="btn btn-sm" style="background:var(--po-accent,#2563eb);color:#fff;border:none;border-radius:8px;font-weight:600;padding:6px 14px;">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი გაყიდვა</span>
            </button>
            {{-- კურიერი დღეს --}}
            <a href="{{ route('exportExcel.courierOrders') }}" class="btn btn-sm" style="background:var(--po-green-soft,#f0fdf4);color:var(--po-green,#16a34a);border:1px solid color-mix(in srgb,#16a34a 25%,transparent);border-radius:8px;font-weight:600;padding:6px 12px;text-decoration:none;">
                <i class="fa fa-file-excel me-1"></i><span class="d-none d-sm-inline">კურიერი დღეს</span>
            </a>
            {{-- Export dropdown --}}
            <div style="position:relative;" id="po-export-wrap">
                <button id="po-export-btn" class="btn btn-sm" style="background:var(--po-bg,#f4f5f7);color:var(--po-text-2,#4b5563);border:1px solid var(--po-border,#e8eaed);border-radius:8px;font-weight:600;padding:6px 12px;">
                    <i class="fa fa-download me-1"></i><span class="d-none d-sm-inline">ექსპორტი</span>
                    <i class="fa fa-chevron-down ms-1" style="font-size:10px;"></i>
                </button>
                <div id="po-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);right:0;z-index:9999;background:var(--po-surface,#fff);border:1.5px solid var(--po-border,#e8eaed);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:180px;overflow:hidden;">
                    <a onclick="exportFilteredPDF();" class="po-drop-item"><i class="fa fa-file-pdf" style="color:var(--po-red,#dc2626);"></i> Filtered PDF</a>
                    <a href="{{ route('exportPDF.productOrderAll') }}" class="po-drop-item"><i class="fa fa-file-pdf" style="color:#aaa;"></i> All PDF</a>
                </div>
            </div>
            {{-- Merge (hidden) --}}
            <button onclick="mergeSelected()" class="btn btn-sm" id="btn-merge" style="display:none;background:var(--po-accent-soft,#eff4ff);color:var(--po-accent,#2563eb);border:1px solid var(--po-accent,#2563eb);border-radius:8px;font-weight:600;">
                <i class="fa fa-link me-1"></i><span class="d-none d-sm-inline">გაერთიანება</span>
            </button>
            {{-- Dark toggle --}}
            <button onclick="togglePoTheme()" class="po-theme-btn" title="Dark Mode" id="po-theme-btn">
                <i class="fa fa-moon" id="po-theme-icon"></i>
            </button>
        </div>
    </div>

    {{-- ── STATS STRIP ── --}}
    <div class="po-stats">
        <div class="po-stat">
            <div class="po-stat-icon" style="background:var(--po-accent-soft);color:var(--po-accent);"><i class="fa fa-cart-shopping"></i></div>
            <div class="po-stat-label">სულ ორდერი</div>
            <div class="po-stat-value" id="stat-total">—</div>
            <div class="po-stat-sub">ბოლო 30 დღე</div>
        </div>
        <div class="po-stat">
            <div class="po-stat-icon" style="background:var(--po-red-soft);color:var(--po-red);"><i class="fa fa-circle-exclamation"></i></div>
            <div class="po-stat-label">დავალიანება</div>
            <div class="po-stat-value" id="stat-debt" style="color:var(--po-red);">—</div>
            <div class="po-stat-sub" id="stat-debt-sub">—</div>
        </div>
        <div class="po-stat">
            <div class="po-stat-icon" style="background:var(--po-green-soft);color:var(--po-green);"><i class="fa fa-check-circle"></i></div>
            <div class="po-stat-label">გადახდილი</div>
            <div class="po-stat-value" id="stat-paid" style="color:var(--po-green);">—</div>
            <div class="po-stat-sub">ამ თვეში</div>
        </div>
        <div class="po-stat">
            <div class="po-stat-icon" style="background:var(--po-purple-soft);color:var(--po-purple);"><i class="fa fa-truck"></i></div>
            <div class="po-stat-label">კურიერთან</div>
            <div class="po-stat-value" id="stat-courier" style="color:var(--po-purple);">—</div>
            <div class="po-stat-sub">გასაგზავნი</div>
        </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="po-filter-bar">
        <div class="po-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" id="dt-search" placeholder="ძებნა ორდერი, კლიენტი...">
        </div>
        <div class="po-pill-group">
            <button class="po-pill active" data-period="all">ყველა</button>
            <button class="po-pill" data-period="today">დღეს</button>
            <button class="po-pill" data-period="week">კვირა</button>
            <button class="po-pill" data-period="month">თვე</button>
            <button class="po-pill" data-period="custom">Custom</button>
        </div>
        <div class="po-custom-dates" id="customDates">
            <input type="date" id="filter-date-from">
            <span style="color:var(--po-text-3);">—</span>
            <input type="date" id="filter-date-to">
            <button class="po-apply-btn" id="applyCustom">გამოყენება</button>
        </div>
        <div class="po-filter-sep"></div>
        {{-- Status dropdown --}}
        <div style="position:relative;" id="po-status-wrap">
            <button id="status-filter-btn" type="button" class="po-select" style="cursor:pointer;min-width:140px;text-align:left;display:flex;align-items:center;gap:6px;">
                <i class="fa fa-filter" style="font-size:10px;opacity:.6;"></i>
                <span id="status-filter-label" style="flex:1;">სტატუსი</span>
                <span style="opacity:.5;font-size:11px;">▾</span>
            </button>
            <div id="po-status-dropdown">
                @foreach($statuses as $status)
                <label>
                    <input type="checkbox" class="status-filter-check" value="{{ $status->id }}"> {{ $status->name }}
                </label>
                @endforeach
            </div>
        </div>
        {{-- Page length --}}
        <select id="dt-page-length" class="po-select" style="width:80px;">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1">ყველა</option>
        </select>
        <div class="po-filter-sep"></div>
        <div class="po-toggle-wrap">
            <label for="toggle-deleted">დავ.</label>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle-deleted" role="switch" style="cursor:pointer;">
            </div>
        </div>
        <div class="po-toggle-wrap">
            <label for="toggle-show-deleted">წაშ.</label>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle-show-deleted" role="switch" style="cursor:pointer;">
            </div>
        </div>
    </div>

    {{-- ── BULK BAR ── --}}
    <div class="po-bulk-bar" id="po-bulk-bar" style="display:none;">
        <i class="fa fa-check-square"></i>
        <span id="po-bulk-count">0 მონიშნული</span>
        <button class="po-bulk-btn" onclick="mergeSelected()"><i class="fa fa-link"></i> გაერთიანება</button>
        <button class="po-bulk-btn" onclick="exportFilteredPDF()"><i class="fa fa-file-pdf"></i> PDF</button>
        <span style="margin-left:auto;opacity:.8;cursor:pointer;font-size:16px;" onclick="clearPoSelection()">✕</span>
    </div>

    {{-- ── TABLE ── --}}
    <div class="po-table-card">
        <div class="table-responsive">
            <table id="products-out-table" class="table w-100">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="check-all" title="ყველას მონიშვნა"></th>
                        <th>№ / სტატუსი</th>
                        <th style="display:none;"></th>{{-- created_at sort --}}
                        <th>პროდუქტი</th>
                        <th>კლიენტი</th>
                        <th>ფინანსები</th>
                        <th>თარიღი</th>
                        <th style="text-align:right;">მოქმედება</th>
                        <th style="display:none;"></th>{{-- cross_ref_html --}}
                        <th style="display:none;"></th>{{-- has_mergeable --}}
                        <th style="display:none;"></th>{{-- children_by_status --}}
                        <th style="display:none;"></th>{{-- group_oldest_date --}}
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap po-page --}}

<div class="modal fade" id="modal-status" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm"> <div class="modal-content" style="border-radius: 8px;">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Change Status</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_order_id">
                <div class="form-group">
                    <label>Select New Status</label>
                    <select id="quick_status_select" class="form-control">
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="saveQuickStatus()" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-image-preview" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="text-align: center; margin-top: 50px;">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            <div class="modal-body" style="position: relative; padding: 0;">
                <button type="button" class="close" data-bs-dismiss="modal" 
                        style="color: #fff; opacity: 1; font-size: 45px; position: absolute; top: -45px; right: 0;">&times;</button>
                <img id="preview-img-full" src="" 
                     style="max-width: 100%; max-height: 85vh; border: 3px solid #fff; border-radius: 4px; box-shadow: 0 0 30px rgba(0,0,0,0.6);">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modal-mail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> მეილის გაგზავნა</h4>
            </div>
           <div class="modal-body">
    <input type="hidden" id="mail_order_id">
    <input type="hidden" id="mail_customer_id">
    <input type="hidden" id="mail_original_email">

    <div class="form-group">
        <label>Email მისამართი</label>
        <input type="email" id="mail_email_input" class="form-control" placeholder="example@gmail.com">
    </div>
    <div class="form-group">
        <label>სათაური</label>
        <input type="text" id="mail_subject" class="form-control" value="თქვენი შეკვეთის ინფორმაცია">
    </div>
    <div class="form-group">
        <label>შეტყობინება <small class="text-muted">(სურვილისამებრ)</small></label>
        <textarea id="mail_body" class="form-control" rows="3" placeholder="დამატებითი შეტყობინება..."></textarea>
    </div>

    {{-- PDF preview hint --}}
    <div style="background:#f9f9f9; border:1px solid #e0e0e0; border-radius:6px; padding:10px 12px; font-size:12px; color:#666;">
        <i class="fa fa-file-pdf-o" style="color:#c0392b;"></i>
        შეკვეთის <strong>Invoice PDF</strong> ავტომატურად დაემატება attachment-ად
    </div>
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-bs-dismiss="modal">გაუქმება</button>
              <button type="button" id="btn-send-mail" onclick="sendMail()" class="btn btn-success">
    <i class="fa fa-paper-plane"></i> გაგზავნა
</button>
               
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-status-log" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-history"></i> სტატუსის ისტორია</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>თარიღი</th>
                            <th>იყო</th>
                            <th>გახდა</th>
                            <th>შეცვალა</th>
                        </tr>
                    </thead>
                    <tbody id="status-log-body">
                        <tr><td colspan="4" class="text-center">იტვირთება...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
    @include('product_order.form_sale')

{{-- ══ Quick Pay Modal ══════════════════════════════════════════ --}}
<div class="modal fade" id="modal-quick-pay" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:10px;">
            <div class="modal-header" style="background:#f8f9fa;">
                <h5 class="modal-title"><i class="fa fa-credit-card me-1"></i> გადახდა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pay_order_id">
                <input type="hidden" id="pay_price_hidden">

                {{-- Product price --}}
                <div style="background:#f0f0f0; border-radius:6px; padding:8px 12px; margin-bottom:12px; font-size:13px;">
                    <div style="color:#888; font-size:11px; text-transform:uppercase; font-weight:700; margin-bottom:2px;">ფასი</div>
                    <span id="pay_price_display" style="font-size:18px; font-weight:800; color:#2d3436;"></span>
                </div>

                {{-- Discount --}}
                <div class="form-group mb-2">
                    <label style="font-size:12px; font-weight:600; color:#636e72;">ფასდაკლება (₾)</label>
                    <input type="number" id="pay_discount" class="form-control form-control-sm" step="0.01" min="0" value="0" oninput="calcPaySummary()">
                </div>

                {{-- Payments --}}
                <div style="font-size:12px; font-weight:600; color:#636e72; margin-bottom:6px; text-transform:uppercase;">გადახდა</div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text" style="width:50px;">TBC</span>
                    <input type="number" id="pay_tbc" class="form-control" step="0.01" min="0" value="0" oninput="calcPaySummary()">
                </div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text" style="width:50px;">BOG</span>
                    <input type="number" id="pay_bog" class="form-control" step="0.01" min="0" value="0" oninput="calcPaySummary()">
                </div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text" style="width:50px;">LIB</span>
                    <input type="number" id="pay_lib" class="form-control" step="0.01" min="0" value="0" oninput="calcPaySummary()">
                </div>
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text" style="width:50px;">Cash</span>
                    <input type="number" id="pay_cash" class="form-control" step="0.01" min="0" value="0" oninput="calcPaySummary()">
                </div>

                {{-- Summary --}}
                <div id="pay_summary" style="text-align:center; font-size:13px; font-weight:700; padding:6px; border-radius:6px; background:#f8f9fa;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-save-pay" onclick="savePayment()">
                    <i class="fa fa-save"></i> შენახვა
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- 🔄 Change Order Modal                                       --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-change" tabindex="-1" role="dialog" data-bs-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius:8px;">
            <form id="form-change">
                @csrf
                <input type="hidden" name="original_sale_id" id="change_original_sale_id">

                <div class="modal-header" style="background:#f39c12; color:#fff; border-radius:8px 8px 0 0;">
                    <button type="button" class="close" data-bs-dismiss="modal" style="color:#fff; opacity:1;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> გაცვლა / დაბრუნება</h4>
                </div>

                <div class="modal-body">

                    {{-- original info --}}
                    <div style="background:#f9f9f9; border:1px solid #ddd; border-radius:6px;
                                padding:10px 14px; margin-bottom:14px; font-size:13px;">
                        <i class="fa fa-cube" style="color:#888;"></i>
                        <strong id="change-orig-product">—</strong>
                        <span id="change-orig-size" class="label label-info" style="margin-left:6px;"></span>
                        <span style="color:#888; margin-left:8px;">Sale #<span id="change-orig-id">—</span></span>
                    </div>

                    {{-- ტიპი --}}
                    <div class="form-group">
                        <label style="font-weight:600;">ტიპი</label>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="return" checked> ↩ დაბრუნება
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="size"> 📐 ზომის გაცვლა
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="product"> 🔄 პროდუქტის გაცვლა
                            </label>
                        </div>
                    </div>

                    {{-- პროდუქტი / ზომა --}}
                    <div id="change-new-fields">
                        <div class="row">
                            <div class="col-md-7" id="change-product-group" style="display:none;">
                                <div class="form-group">
                                    <label style="font-weight:600;">ახალი პროდუქტი</label>
                                    <select name="product_id" id="change_product_id" class="form-control" required>
                                        <option value="">— აირჩიე —</option>
                                        @foreach($all_products as $product)
                                            <option value="{{ $product->id }}"
                                                data-sizes="{{ $product->sizes }}"
                                                data-price-ge="{{ $product->price_geo }}">
                                                {{ $product->name }}
                                                @if($product->product_code)({{ $product->product_code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label style="font-weight:600;">ახალი ზომა</label>
                                    <select name="product_size" id="change_size" class="form-control" required>
                                        <option value="">— ზომა —</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Stock info --}}
                        <div id="change-stock-info" style="display:none; background:#f4f4f4;
                             border-radius:8px; padding:10px 14px; margin-bottom:10px;">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase;
                                        color:#888; margin-bottom:6px;">მიმდინარე ნაშთი</div>
                            <div class="row text-center">
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#3c763d;" id="chg-si-physical">0</div>
                                    <div style="font-size:10px; color:#888;">📦 ფიზიკური</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#31708f;" id="chg-si-incoming">0</div>
                                    <div style="font-size:10px; color:#888;">🚚 გზაში</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#8a6d3b;" id="chg-si-reserved">0</div>
                                    <div style="font-size:10px; color:#888;">🔒 დაჯავშნ.</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800;" id="chg-si-available">0</div>
                                    <div style="font-size:10px; color:#888;">✅ თავისუფალი</div>
                                </div>
                            </div>
                            <div style="margin-top:8px; text-align:center;" id="chg-si-badge"></div>
                        </div>

                        {{-- ფასთა სხვაობა --}}
                        <div id="change-price-diff-block" style="display:none; background:#fff8e1;
                             border:1px solid #ffe082; border-radius:6px; padding:10px 14px;
                             margin-bottom:10px; font-size:13px;">
                            <span style="color:#888;">ფასთა სხვაობა:</span>
                            <strong id="change-price-diff" style="font-size:15px; margin-left:6px;">—</strong>
                        </div>
                    </div>

                    {{-- საკურიერო --}}
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-weight:600;"><i class="fa fa-truck"></i> კურიერი</label>
                        <div style="display:flex; gap:14px; flex-wrap:wrap; background:#f4f4f4;
                                    border:1px solid #ddd; border-radius:6px; padding:8px 12px;">
                            <label style="margin:0; font-weight:normal; cursor:pointer;">
                                <input type="radio" name="courier_type" value="none" checked> არ გამოიყენება
                            </label>
                            <label style="margin:0; font-weight:normal; cursor:pointer;">
                                <input type="radio" name="courier_type" value="tbilisi">
                                თბილისი (+{{ $courier->tbilisi_price ?? 6 }}₾)
                            </label>
                            <label style="margin:0; font-weight:normal; cursor:pointer;">
                                <input type="radio" name="courier_type" value="region">
                                რაიონი (+{{ $courier->region_price ?? 9 }}₾)
                            </label>
                            <label style="margin:0; font-weight:normal; cursor:pointer;">
                                <input type="radio" name="courier_type" value="village">
                                სოფელი (+{{ $courier->village_price ?? 13 }}₾)
                            </label>
                        </div>
                        <div id="change-courier-note" style="font-size:11px; color:#888; margin-top:4px;">
                            ↩ დაბრუნებისას — კურიერი შესყიდვაზე ჩაიწერება
                        </div>
                    </div>

                    {{-- გადახდა --}}
                    <div class="well well-sm" style="background:#f4f4f4; border:1px solid #ddd; padding:10px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            <i class="fa fa-credit-card"></i> გადახდა (სხვაობა)
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon">TBC</span>
                            <input type="number" name="paid_tbc" class="form-control" placeholder="0" step="0.01" value="0">
                            <span class="input-group-addon">BOG</span>
                            <input type="number" name="paid_bog" class="form-control" placeholder="0" step="0.01" value="0">
                            <span class="input-group-addon">Cash</span>
                            <input type="number" name="paid_cash" class="form-control" placeholder="0" step="0.01" value="0">
                        </div>
                    </div>

                    {{-- შენიშვნა --}}
                    <div class="form-group" style="margin-top:10px;">
                        <label style="font-weight:600;">შენიშვნა</label>
                        <textarea name="comment" id="change_comment" class="form-control" rows="2"
                                  placeholder="შენიშვნა..."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-refresh"></i> დარეგისტრირება
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('bot')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/validator/validator.min.js') }}"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script type="text/javascript">

        // =====================
        // DataTable
        // =====================
        var save_method;
        var isAdmin        = {{ auth()->user()->role == 'admin' ? 'true' : 'false' }};
        var isSaleOperator = {{ auth()->user()->role == 'sale_operator' ? 'true' : 'false' }};
        var mergeMode      = false;

function fmtDate(dt) {
    if (!dt) return '';
    var d = new Date(dt);
    return ('0'+d.getDate()).slice(-2) + '.' + ('0'+(d.getMonth()+1)).slice(-2) + '.' + d.getFullYear();
}
function fmtTime(dt) {
    if (!dt) return '';
    var d = new Date(dt);
    return ('0'+d.getHours()).slice(-2) + ':' + ('0'+d.getMinutes()).slice(-2);
}

var columns = [
    // Col 0: Checkbox only
    {
        data: null,
        orderable: false,
        searchable: false,
        width: '36px',
        render: function(data) {
            return '<input type="checkbox" class="row-check" data-id="' + data.id + '" data-status="' + data.status_id + '">';
        }
    },
    // Col 1: Order identity
    {
        data: null,
        orderable: false,
        searchable: true,
        responsivePriority: 1,
        /* placeholder width — keep old signature intact */
        width: '160px',
        render: function(data) {
            var isGroup = data.is_primary && data.children_count > 1;

            // Store children for expand
            if (data.is_primary && data.children_count > 0) {
                window._childrenStore = window._childrenStore || {};
                window._childrenStore[data.id] = Array.isArray(data.children_json)
                    ? data.children_json
                    : (typeof data.children_json === 'string' ? JSON.parse(data.children_json || '[]') : []);
            }

            // Courier tag helper
            var courierPrice = parseFloat(data.courier_price_tbilisi) || parseFloat(data.courier_price_region) || parseFloat(data.courier_price_village) || 0;
            var courierHtml = '';
            if (courierPrice > 0) {
                var cLabel = parseFloat(data.courier_price_tbilisi) > 0 ? 'თბ'
                           : parseFloat(data.courier_price_region)  > 0 ? 'რაი' : 'სოფ';
                courierHtml = '<div style="margin-top:4px;"><span class="po-courier-tag"><i class="fa fa-truck" style="font-size:9px;"></i> ' + cLabel + ' ' + courierPrice + '₾</span></div>';
            }

            if (isGroup) {
                var groups = Array.isArray(data.children_by_status)
                    ? data.children_by_status
                    : (typeof data.children_by_status === 'string' ? JSON.parse(data.children_by_status || '[]') : []);
                var badges = '<div class="po-group-badges">';
                groups.forEach(function(g) {
                    badges += '<span class="label label-' + g.color + '"><i class="fa fa-cube" style="font-size:9px;"></i> ' + g.count + '× ' + g.name + '</span>';
                });
                badges += '</div>';
                return '<span class="po-order-num group">⬡ ' + (data.order_number || ('G'+data.id)) + '</span>'
                    + badges
                    + (data.status_label || '')
                    + courierHtml
                    + '<div><span class="po-expand-btn expand-btn" data-id="' + data.id + '">'
                    + '<i class="fa fa-chevron-right"></i> ' + data.children_count + ' შვილი'
                    + '</span></div>';
            }

            // Regular row
            var orderNo  = data.order_number || ('S'+data.id);
            var crossRef = data.cross_ref_html
                ? '<div style="font-size:10px;color:var(--po-text-3,#9ca3af);margin-top:2px;">' + data.cross_ref_html + '</div>' : '';
            var mergeHint = '';
            if (data.has_mergeable && data.customer_id && data.status !== 'deleted') {
                mergeHint = '<span class="po-merge-hint merge-search-btn" data-customer-id="' + data.customer_id + '" title="ამ კლიენტის ორდერები"><i class="fa fa-link"></i></span>';
            }
            return '<div style="display:flex;align-items:center;gap:5px;margin-bottom:4px;flex-wrap:nowrap;">'
                + '<span class="po-order-num">' + orderNo + '</span>'
                + mergeHint
                + '</div>'
                + (data.status_label || '')
                + courierHtml
                + crossRef;
        }
    },
    // Col 2: created_at — hidden, sort only
    { data: 'created_at', name: 'created_at', visible: false },
    // Col 3: Product
    {
        data: null, orderable: false, searchable: false, responsivePriority: 3,
        render: function(data) {
            var photo = data.show_photo
                ? '<div class="po-product-thumb">' + data.show_photo + '</div>' : '';
            return '<div class="po-product-cell">'
                + photo
                + '<div style="font-size:12.5px;min-width:0;">' + (data.product_info || '') + '</div>'
                + '</div>';
        }
    },
    // Col 4: Customer
    {
        data: null, orderable: false, searchable: false, responsivePriority: 4,
        render: function(data) {
            return '<div style="font-size:13px;">' + (data.customer_name || '') + '</div>';
        }
    },
    // Col 5: Finance
    {
        data: null, orderable: false, searchable: false, responsivePriority: 5,
        render: function(data) {
            if (data.is_primary && data.children_count > 1) {
                return '<div style="font-size:12.5px;">' + (data.payment || '') + '</div>';
            }
            var geo  = parseFloat(data.price_georgia || 0) - parseFloat(data.discount || 0);
            var paid = parseFloat(data.paid_tbc || 0) + parseFloat(data.paid_bog || 0)
                     + parseFloat(data.paid_lib || 0) + parseFloat(data.paid_cash || 0);
            var pct      = geo > 0 ? Math.min(paid / geo, 1) : 1;
            var isPaid   = (geo - paid) <= 0.01;
            var pColor   = pct >= 1 ? 'var(--po-green,#16a34a)' : (pct > 0 ? 'var(--po-amber,#d97706)' : 'var(--po-red,#dc2626)');
            var tag = isPaid
                ? '<span class="po-paid-tag"><i class="fa fa-check" style="font-size:9px;"></i> გადახდილი</span>'
                : '<span class="po-debt-tag"><i class="fa fa-circle-exclamation" style="font-size:9px;"></i> ' + (geo-paid).toFixed(2) + ' ₾</span>';
            return '<div class="po-finance-total">' + geo.toFixed(2) + ' ₾</div>'
                + (parseFloat(data.discount || 0) > 0 ? '<div style="font-size:10px;color:var(--po-text-3);">🏷️ -' + data.discount + '₾</div>' : '')
                + '<div class="po-paid-row">'
                + '<div class="po-paid-bar"><div class="po-paid-fill" style="width:' + (pct*100).toFixed(0) + '%;background:' + pColor + ';"></div></div>'
                + '</div>'
                + '<div style="margin-top:3px;">' + tag + '</div>';
        }
    },
    // Col 6: Date
    {
        data: null, orderable: false, searchable: false, responsivePriority: 6,
        render: function(data) {
            return '<div class="po-date-cell">' + fmtDate(data.created_at)
                + '<small>' + fmtTime(data.created_at) + '</small></div>';
        }
    },
    // Col 7: Actions
    {
        data: null, orderable: false, searchable: false, responsivePriority: 2,
        render: function(data) {
            if (data.status === 'deleted') return '<div class="po-actions">' + (data.action || '') + '</div>';
            if (data.is_primary && data.children_count > 1) {
                return '<div class="po-actions">' + (data.action || '') + '</div>';
            }
            var geo  = parseFloat(data.price_georgia || 0) - parseFloat(data.discount || 0);
            var paid = parseFloat(data.paid_tbc || 0) + parseFloat(data.paid_bog || 0)
                     + parseFloat(data.paid_lib || 0) + parseFloat(data.paid_cash || 0);
            var pct      = geo > 0 ? paid / geo : 1;
            var isPaid   = (geo - paid) <= 0.01;
            var payClass = isPaid ? 'po-pay-paid' : (pct > 0 ? 'po-pay-partial' : 'po-pay-debt');
            var payBtn   = '';
            if (!isSaleOperator) {
                payBtn = '<a onclick="openPayModal('
                    + data.id + ',' + (data.price_georgia||0) + ',' + (data.discount||0) + ','
                    + (data.paid_tbc||0) + ',' + (data.paid_bog||0) + ',' + (data.paid_lib||0) + ',' + (data.paid_cash||0)
                    + ')" class="btn btn-xs ' + payClass + '" title="გადახდა"><i class="fa fa-credit-card"></i></a>';
            }
            var actionHtml = (data.action || '').replace(/^<div/, '<div class="po-actions"');
            return actionHtml.replace('</div>', payBtn + '</div>');
        }
    },
    // Hidden cols
    { data: 'cross_ref_html',     name: 'cross_ref_html',     orderable: false, searchable: false, visible: false },
    { data: 'has_mergeable',      name: 'has_mergeable',      orderable: false, searchable: false, visible: false },
    { data: 'children_by_status', name: 'children_by_status', orderable: false, searchable: false, visible: false },
    { data: 'group_oldest_date',  name: 'group_oldest_date',  orderable: false, searchable: false, visible: false },
];

var table = $('#products-out-table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: "{{ route('api.productsOut') }}",
    columns: columns,
    order: [[2, 'desc']],
    dom: 't<"d-flex justify-content-between align-items-center mt-2 px-3 pb-3"ip>',
    pageLength: 25,
    language: { info: '_START_–_END_ / _TOTAL_', paginate: { previous: '‹', next: '›' } },
    createdRow: function(row, data) {
        if (data.is_primary && data.children_count > 1) { $(row).addClass('po-group-row'); return; }
        if (data.status_id == 6) { $(row).addClass('po-row-exchanged'); return; }
        if (data.status_id == 5) { $(row).addClass('po-row-returned');  return; }
        if (data.original_sale_id) { $(row).addClass('po-row-change'); return; }
        var geo  = parseFloat(data.price_georgia || 0) - parseFloat(data.discount || 0);
        var paid = parseFloat(data.paid_tbc || 0) + parseFloat(data.paid_bog || 0) +
                   parseFloat(data.paid_lib || 0) + parseFloat(data.paid_cash || 0);
        if ((geo - paid) > 0.01) { $(row).addClass('po-row-debt'); }
    },
});

        // =====================
        // Custom length + search
        // =====================
        $('#dt-page-length').on('change', function() {
            table.page.len(parseInt($(this).val())).draw();
        });
        $('#dt-search').on('input', function() {
            table.search($(this).val()).draw();
        });

        // =====================
        // Helper: courier by city
        // =====================
        function updateCourierByCity(cityId) {
            if (cityId === 1) {
                $('input[name="courier_type"][value="tbilisi"]').prop('checked', true);
            } else {
                $('input[name="courier_type"][value="none"]').prop('checked', true);
            }
        }

        // =====================
        // Select2 — customer
        // =====================
        $('#customer_id_sale').select2({
            dropdownParent: $('#modal-sale'),
            placeholder: '-- Choose Customer --',
            allowClear: true
        });
        // customer info ჩვენება — მხოლოდ ერთხელ
        $('#customer_id_sale').on('change', function() {
            var selected = $(this).find('option:selected');

            if (!selected.val()) {
                $('#customer_info_fields').hide();
                return;
            }

            var address = selected.data('address') || '';
            var altTel  = selected.data('alt') || '';
            var comment = selected.data('comment') || '';
            var cityId  = selected.data('city-id') || '';

            $('#customer_tel').text(selected.data('tel') || '');
            $('#customer_address_input').val(address);
            $('#customer_alt_tel_input').val(altTel);
            $('#customer_city_select').val(cityId);

            if (comment) {
                $('#customer_comment').text(comment);
                $('#customer_comment_wrap').show();
            } else {
                $('#customer_comment_wrap').hide();
            }

            // ორიგინალი მნიშვნელობები შენახვა
            $('#customer_address_input').data('original', address);
            $('#customer_alt_tel_input').data('original', altTel);
            $('#customer_city_select').data('original', String(cityId));

            $('#customer_info_fields').show();

            updateCourierByCity(parseInt(cityId));

            // city dropdown change → courier_type auto-select
            $('#customer_city_select').off('change.courier').on('change.courier', function() {
                updateCourierByCity(parseInt($(this).val()));
            });
        });

        // =====================
        // კურიერი — radio buttons
        // =====================
        // courier_type radio-ს ცვლილება არ საჭიროებს დამატებით ლოგიკას —
        // მნიშვნელობა პირდაპირ იგზავნება სერვერზე name="courier_type"-ით

        // =====================
        // ჯამური გამოთვლა (multi-row)

        // =====================
        // Add Sale
        // =====================
        var saleRowIndex = 0;

        function addSaleForm() {
            save_method = 'add';
            isEditMode  = false;
            saleRowIndex = 0;
            $('#form-sale-content input[name=_method]').val('POST');
            $('#form-sale-content')[0].reset();
            $('#modal-sale-title').text('ახალი გაყიდვა');

            $('#sale-items-container').empty();
            addSaleLine({});

            $('#target_image').hide();
            $('#no_image_text').show();
            $('#customer_id_sale').val(null).trigger('change');
            $('#customer_info_fields').hide();
            $('input[name="courier_type"][value="none"]').prop('checked', true);
            $('#customer_city_select, #customer_address_input, input[name="courier_type"]')
                .prop('disabled', false).removeAttr('title');
            $('#add-sale-line').show();
            $('#modal-sale').modal('show');
        }

        // =====================
        // Add Sale Line (dynamic row)
        // =====================
        function addSaleLine(defaults) {
            defaults = defaults || {};
            var idx  = saleRowIndex++;
            var optHtml = $('#product-options-template').html();

            var lockProd = defaults.lockProduct ? 'disabled' : '';
            var lockSize = defaults.lockProduct ? 'disabled' : '';
            var canRemove = (!defaults.editMode) ? '' : 'disabled';

            var row =
            '<div class="sale-item-row" data-idx="' + idx + '">' +
                '<div class="row g-2 align-items-end">' +

                    '<div class="col-12 col-sm-5">' +
                        '<div class="sale-col-label">პროდუქტი</div>' +
                        '<select name="items[' + idx + '][product_id]" class="form-select form-select-sm sale-product-select" required ' + lockProd + ' style="border-radius:8px;border:1.5px solid #e0e4f0;">' +
                            optHtml +
                        '</select>' +
                    '</div>' +

                    '<div class="col-6 col-sm-2">' +
                        '<div class="sale-col-label">ზომა</div>' +
                        '<select name="items[' + idx + '][product_size]" class="form-select form-select-sm sale-size-select" ' + lockSize + ' style="border-radius:8px;border:1.5px solid #e0e4f0;">' +
                            '<option value="">— ზომა —</option>' +
                        '</select>' +
                    '</div>' +

                    '<div class="col-6 col-sm-2">' +
                        '<div class="sale-col-label">ფასდაკლება</div>' +
                        '<div class="input-group input-group-sm">' +
                            '<span class="input-group-text" style="background:#fdf0f8;border:1.5px solid #e0e4f0;border-right:0;border-radius:8px 0 0 8px;color:#9b59b6;font-weight:700;">🏷</span>' +
                            '<input type="number" name="items[' + idx + '][discount]" class="form-control sale-discount" value="0" min="0" step="0.01" placeholder="0.00" style="border:1.5px solid #e0e4f0;border-left:0;border-radius:0 8px 8px 0;">' +
                        '</div>' +
                    '</div>' +

                    '<div class="col-6 col-sm-2">' +
                        '<div class="sale-col-label">ფასი</div>' +
                        '<div class="d-flex gap-1 align-items-center">' +
                            '<span class="price-pill-gel sale-price-gel">0 ₾</span>' +
                            '<span class="price-pill-usd sale-price-usd">$0</span>' +
                        '</div>' +
                        '<input type="hidden" name="items[' + idx + '][price_georgia]" value="0" class="sale-hidden-gel">' +
                        '<input type="hidden" name="items[' + idx + '][price_usa]"     value="0" class="sale-hidden-usd">' +
                    '</div>' +

                    '<div class="col-6 col-sm-1 d-flex align-items-end justify-content-end">' +
                        '<button type="button" class="btn btn-sm remove-sale-line" ' + canRemove + ' ' +
                            'style="background:#fff0f0;border:1.5px solid #fcc;color:#e74c3c;border-radius:8px;padding:5px 10px;">' +
                            '<i class="fa fa-trash"></i>' +
                        '</button>' +
                    '</div>' +

                '</div>' +
                '<div class="sale-row-stock" style="display:none;">' +
                    '<span>📦 <b class="si-physical">0</b></span>' +
                    '<span class="ms-2">🚚 <b class="si-incoming">0</b></span>' +
                    '<span class="ms-2">🔒 <b class="si-reserved">0</b></span>' +
                    '<span class="ms-2 text-success fw-bold">✅ <b class="si-available">0</b></span>' +
                '</div>' +
            '</div>';

            var $row = $(row);
            $('#sale-items-container').append($row);

            // Select2 on product select
            $row.find('.sale-product-select').select2({
                dropdownParent: $('#modal-sale'),
                placeholder: '— პროდუქტი —',
                allowClear: true,
                width: '100%',
                templateResult: function(opt) {
                    if (!opt.id) return $('<span>' + opt.text + '</span>');
                    var img = $(opt.element).data('image');
                    var $el = $('<span style="display:flex;align-items:center;gap:8px;"></span>');
                    if (img) {
                        $el.append('<img src="' + img + '" style="width:32px;height:32px;object-fit:cover;border-radius:3px;flex-shrink:0;">');
                    } else {
                        $el.append('<span style="width:32px;height:32px;background:#f0f0f0;border-radius:3px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa fa-image" style="color:#ccc;font-size:13px;"></i></span>');
                    }
                    $el.append('<span>' + opt.text + '</span>');
                    return $el;
                },
                templateSelection: function(opt) {
                    if (!opt.id) return $('<span>' + opt.text + '</span>');
                    var img = $(opt.element).data('image');
                    var $el = $('<span style="display:flex;align-items:center;gap:6px;"></span>');
                    if (img) {
                        $el.append('<img src="' + img + '" style="width:22px;height:22px;object-fit:cover;border-radius:2px;flex-shrink:0;">');
                    }
                    $el.append('<span>' + opt.text + '</span>');
                    return $el;
                }
            });

            // Set defaults
            if (defaults.product_id) {
                $row.find('.sale-product-select').val(defaults.product_id).trigger('change');

                if (defaults.product_size) {
                    var checkSize = setInterval(function() {
                        if ($row.find('.sale-size-select option').length > 1) {
                            clearInterval(checkSize);
                            $row.find('.sale-size-select').val(defaults.product_size);
                            if (defaults.price_georgia) {
                                $row.find('.sale-price-gel').text(defaults.price_georgia + ' ₾');
                                $row.find('.sale-hidden-gel').val(defaults.price_georgia);
                            }
                            if (defaults.price_usa) {
                                $row.find('.sale-price-usd').text('$' + defaults.price_usa);
                                $row.find('.sale-hidden-usd').val(defaults.price_usa);
                            }
                        }
                    }, 100);
                    setTimeout(function() { clearInterval(checkSize); }, 3000);
                }
            }
            if (defaults.discount !== undefined) {
                $row.find('.sale-discount').val(defaults.discount);
            }

        }

        // =====================
        // Remove Sale Line
        // =====================
        $(document).on('click', '.remove-sale-line', function() {
            $(this).closest('.sale-item-row').remove();
        });

        // Add product line button
        $('#add-sale-line').on('click', function() {
            addSaleLine({});
        });

        // =====================
        // Edit Sale
        // =====================
function editForm(id) {
    save_method  = 'edit';
    isEditMode   = true;
    saleRowIndex = 0;
    $('#form-sale-content input[name=_method]').val('PATCH');

    $.ajax({
        url: "{{ url('productsOut') }}/" + id + "/edit",
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            $('#form-sale-content')[0].reset();
            $('#modal-sale-title').text('გაყიდვის რედაქტირება');
            $('#modal-sale input[name="id"]').val(data.id);

            var statusId   = data.status_id ? parseInt(data.status_id) : 1;
            var lockProd   = (statusId >= 4);

            // Build product defaults; handle inactive product
            var cp = data.current_product;
            var prodId = data.product_id;

            // If inactive product, inject into template clone so addSaleLine can use it
            if (cp && cp.product_status == 0) {
                var tpl = $('#product-options-template');
                tpl.find('option[data-inactive="1"]').remove();
                tpl.append(
                    $('<option>', { value: cp.id, text: cp.name + ' (Inactive)' })
                        .attr('data-inactive', '1')
                        .attr('data-price-ge', cp.price_geo)
                        .attr('data-price-us', cp.price_usa)
                        .attr('data-sizes', cp.sizes || '')
                        .attr('data-image', cp.image || '')
                );
            }

            $('#sale-items-container').empty();
            addSaleLine({
                product_id:    prodId,
                product_size:  data.product_size,
                price_georgia: data.price_georgia,
                price_usa:     data.price_usa,
                discount:      data.discount || 0,
                editMode:      true,
                lockProduct:   lockProd
            });

            $('#add-sale-line').hide();

            // Fetch stock info for the pre-filled product/size
            if (prodId && data.product_size) {
                var $firstRow = $('#sale-items-container .sale-item-row').first();
                $.get("{{ route('warehouse.stockInfo') }}", { product_id: prodId, size: data.product_size }, function(sd) {
                    _updateRowStock($firstRow, sd, prodId, data.product_size);
                });
            }

            $('#customer_id_sale').val(data.customer_id).trigger('change');
            $('#form-sale-content textarea[name="comment"]').val(data.comment || '');

            setTimeout(function() {
                if (data.order_address != null) {
                    $('#customer_address_input').val(data.order_address).data('original', data.order_address);
                }
                if (data.order_alt_tel != null) {
                    $('#customer_alt_tel_input').val(data.order_alt_tel).data('original', data.order_alt_tel);
                }
                if (data.order_city_id != null) {
                    $('#customer_city_select').val(data.order_city_id).data('original', String(data.order_city_id));
                }

                var isMerged = !!(data.merged_id);
                $('#customer_city_select, #customer_address_input').prop('disabled', isMerged);
                $('input[name="courier_type"]').prop('disabled', isMerged);
                if (isMerged) {
                    $('#customer_city_select, #customer_address_input')
                        .attr('title', 'ჯგუფური ორდერის მისამართი/ქალაქი ვერ შეიცვლება');
                    $('input[name="courier_type"]')
                        .attr('title', 'ჯგუფური ორდერის კურიერი ვერ შეიცვლება');
                } else {
                    $('#customer_city_select, #customer_address_input').removeAttr('title');
                    $('input[name="courier_type"]').removeAttr('title');
                }
            }, 80);

            setTimeout(function() {
                $('input[name="courier_type"][value="' + (data.courier_servise_local || 'none') + '"]').prop('checked', true);
            }, 50);

            $('#modal-sale').modal('show');
        },
        error: function() {
            swal("შეცდომა", "მონაცემების წამოღება ვერ მოხერხდა", "error");
        }
    });
}
        // =====================
        // Product change (delegated — per-row)
        // =====================
        var isEditMode = false;

        $(document).on('change', '.sale-product-select', function() {
            var $row     = $(this).closest('.sale-item-row');
            var selected = $(this).find('option:selected');

            if (!isEditMode) {
                var priceGe = selected.data('price-ge') || 0;
                var priceUs = selected.data('price-us') || 0;
                $row.find('.sale-price-gel').text(priceGe + ' ₾');
                $row.find('.sale-hidden-gel').val(priceGe);
                $row.find('.sale-price-usd').text('$' + priceUs);
                $row.find('.sale-hidden-usd').val(priceUs);
            }

            // Image preview — last changed product
            var imageUrl = selected.data('image');
            if (imageUrl) {
                $('#target_image').attr('src', imageUrl).show();
                $('#no_image_text').hide();
            } else {
                $('#target_image').hide();
                $('#no_image_text').show();
            }

            // Sizes
            var sizesRaw  = selected.data('sizes');
            var $sizeSelect = $row.find('.sale-size-select');
            $sizeSelect.empty();

            if (sizesRaw && sizesRaw.toString().trim() !== '') {
                $sizeSelect.append('<option value="">— ზომა —</option>');
                sizesRaw.toString().split(',').forEach(function(s) {
                    s = s.trim();
                    if (s !== '') $sizeSelect.append('<option value="' + s + '">' + s + '</option>');
                });
                $sizeSelect.prop('required', true);
            } else {
                $sizeSelect.append('<option value="">— არ არის —</option>');
                $sizeSelect.prop('required', false);
            }

            // Stock info (product level only — size not yet selected)
            var productId = selected.val();
            if (productId) {
                $.get("{{ route('warehouse.stockInfo') }}", { product_id: productId }, function(data) {
                    _updateRowStock($row, data, productId, null);
                });
            } else {
                $row.find('.sale-row-stock').hide();
            }
        });

        $(document).on('change', '.sale-size-select', function() {
            var $row      = $(this).closest('.sale-item-row');
            var productId = $row.find('.sale-product-select').val();
            var size      = $(this).val();

            if (!isEditMode && productId && size) {
                $.get("{{ url('api/fifo-prices') }}", { product_id: productId, size: size }, function(fifo) {
                    $row.find('.sale-price-gel').text((fifo.price_georgia || 0) + ' ₾');
                    $row.find('.sale-hidden-gel').val(fifo.price_georgia || 0);
                    $row.find('.sale-price-usd').text('$' + (fifo.cost_price || 0));
                    $row.find('.sale-hidden-usd').val(fifo.cost_price || 0);
                });
            }

            if (productId && size) {
                $.get("{{ route('warehouse.stockInfo') }}", { product_id: productId, size: size }, function(data) {
                    _updateRowStock($row, data, productId, size);
                });
            } else {
                $row.find('.sale-row-stock').hide();
            }
        });

        function _updateRowStock($row, data, productId, size) {
            var physical  = data.physical_qty  || 0;
            var incoming  = data.incoming_qty  || 0;
            var reserved  = data.reserved_qty  || 0;
            var available = parseInt(data.available != null ? data.available : (data.available_qty || 0));

            // Count how many OTHER rows in the form select the same product+size
            var otherCount = 0;
            if (productId && size) {
                $('#sale-items-container .sale-item-row').not($row).each(function() {
                    if ($(this).find('.sale-product-select').val() == productId &&
                        $(this).find('.sale-size-select').val()    == size) {
                        otherCount++;
                    }
                });
            }
            var adjustedAvail = available - otherCount;

            var $s = $row.find('.sale-row-stock');
            $s.find('.si-physical').text(physical);
            $s.find('.si-incoming').text(incoming);
            $s.find('.si-reserved').text(reserved);
            $s.find('.si-available').text(adjustedAvail);

            // Warning if another row already uses this slot
            $s.find('.si-duplicate-warn').remove();
            if (otherCount > 0) {
                var warnColor = adjustedAvail <= 0 ? 'red' : '#e67e22';
                var warnMsg   = adjustedAvail <= 0
                    ? '⚠️ ამ ფორმაში ეს ნაშთი უკვე დარეზერვებულია!'
                    : '⚠️ ' + otherCount + ' სხვა სტრიქონი ირჩევს ამ ზომას';
                $s.append('<span class="si-duplicate-warn" style="color:' + warnColor + '; font-weight:700; margin-left:6px; font-size:11px;">' + warnMsg + '</span>');
            }

            $s.show();
        }

        // =====================
        // Sale Form Submit
        // =====================
        $(document).on('submit', '#form-sale-content', function(e) {
            e.preventDefault();
            var form = $(this);

            var customerId  = $('#customer_id_sale').val();
            var newAddress  = String($('#customer_address_input').val() || '');
            var newAltTel   = String($('#customer_alt_tel_input').val() || '');
            var newCityId   = String($('#customer_city_select').val() || '');
            var origAddress = String($('#customer_address_input').data('original') || '');
            var origAltTel  = String($('#customer_alt_tel_input').data('original') || '');
            var origCityId  = String($('#customer_city_select').data('original') || '');

            var addressChanged  = customerId && (newAddress.trim() !== origAddress.trim());
            var altTelChanged   = customerId && (newAltTel.trim() !== origAltTel.trim());
            var cityChanged     = customerId && (newCityId !== origCityId);
            var customerChanged = addressChanged || altTelChanged || cityChanged;

            if (customerChanged) {
                var changedFields = [];
                if (addressChanged) changedFields.push('მისამართი');
                if (altTelChanged)  changedFields.push('ალტ. ტელეფონი');
                if (cityChanged)    changedFields.push('ქალაქი');

                window._pendingSaleForm = form;

                swal({
                    title: 'Customer-ის მონაცემები შეიცვალა',
                    text: changedFields.join(' და ') + ' — Customer-შიც განვაახლოთ?',
                    type: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#00a65a',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'კი, განვაახლოთ',
                    cancelButtonText: 'არა, მხოლოდ ორდერში',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function(result) {
                    var f = window._pendingSaleForm;
                    window._pendingSaleForm = null;
                    var updateCust = (result && result.isConfirmed) ? '1' : '0';
                    submitSaleForm(f, updateCust);
                }).catch(function() {
                    var f = window._pendingSaleForm;
                    window._pendingSaleForm = null;
                    if (f) submitSaleForm(f, '0');
                });
            } else {
                submitSaleForm(form, '0');
            }
        });

        function submitSaleForm(form, updateCustomer) {
            var id  = form.find('input[name="id"]').val();
            var url = (save_method == 'add') ? "{{ url('productsOut') }}" : "{{ url('productsOut') }}/" + id;

            var $locked = form.find(':disabled');
            $locked.prop('disabled', false);
            var formData = new FormData(form[0]);
            $locked.prop('disabled', true);

            formData.append('update_customer', updateCustomer);

            // In edit mode, inject flat product fields so update() can read them
            if (save_method === 'edit') {
                var $firstRow = $('#sale-items-container .sale-item-row').first();
                formData.set('product_id',   $firstRow.find('.sale-product-select').val() || '');
                formData.set('product_size', $firstRow.find('.sale-size-select').val() || '');
                formData.set('price_georgia',$firstRow.find('.sale-hidden-gel').val() || 0);
                formData.set('price_usa',    $firstRow.find('.sale-hidden-usd').val() || 0);
                formData.set('discount',     $firstRow.find('.sale-discount').val() || 0);
            }

            $.ajax({
                url: url,
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    $('#modal-sale').modal('hide');
                    table.ajax.reload();

                    if (updateCustomer === '1') {
                        var custId    = $('#customer_id_sale').val();
                        var newAddr   = $('#customer_address_input').val();
                        var newAlt    = $('#customer_alt_tel_input').val();
                        var newCityId = $('#customer_city_select').val();
                        var newCityTx = $('#customer_city_select option:selected').text();
                        var $opt      = $('#customer_id_sale option[value="' + custId + '"]');
                        if ($opt.length) {
                            $opt.data('address', newAddr).attr('data-address', newAddr);
                            $opt.data('alt',     newAlt).attr('data-alt',     newAlt);
                            $opt.data('city-id', newCityId).attr('data-city-id', newCityId);
                            $opt.data('city',    newCityTx).attr('data-city',    newCityTx);
                        }
                    }

                    swal({ title: 'წარმატება!', text: data.message, type: 'success' });
                },
                error: function(xhr) {
                    var msg = "მონაცემები ვერ შეინახა";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        try { msg = JSON.parse(xhr.responseText).message; } catch(e) {}
                    }
                    swal({ title: 'შეცდომა', text: msg, type: 'error' });
                }
            });
        }

        // =====================
        // Customer Form Submit — მხოლოდ ერთხელ
        // =====================
        $(document).on('submit', '#form-item', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                url: "{{ url('customers') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(data) {
                    $('#modal-form').modal('hide');

                    var newOption = new Option(
                        data.name + ' (' + data.tel + ')',
                        data.id,
                        true,
                        true
                    );
                    $(newOption).data('address', data.address || '');
                    $(newOption).data('city',    data.city_name || '');
                   $(newOption).data('city-id', data.city_id || 0);
                    $(newOption).data('tel',     data.tel || '');
                    $(newOption).data('alt',     data.alternative_tel || '');
                    $(newOption).data('comment', data.comment || '');

                    $('#customer_id_sale').append(newOption).trigger('change');
                    $('#form-item')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var msg = Object.values(errors).flat().join('\n');
                        swal("შეცდომა", msg, "error");
                    } else {
                        swal("შეცდომა", "ვერ შეინახა", "error");
                    }
                }
            });
        });

        // =====================
        // Delete
        // =====================
        function deleteData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({
        title: 'დარწმუნებული ხართ?',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ, წაშალე!'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id,
            type: "POST",
            data: {'_method': 'DELETE', '_token': csrf_token},
            success: function(data) {
                table.ajax.reload();
                swal("წაშლილია!", data.message, "success");
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა წაშლისას!';
                swal("შეცდომა", msg, "error");
            }
        });
    });
}

        // =====================
        // Customer Create Modal
        // =====================
        function openCustomerCreate() {
            // modal-sale არ ვხურავთ — scroll-ის პრობლემის თავიდან ასაცილებლად
            $('#modal-form').modal('show');
        }

        // modal-form დაიხურა → body-ს modal-open class დავუბრუნოთ
        $('#modal-form').on('hidden.bs.modal', function() {
            if ($('#modal-sale').hasClass('in') || $('#modal-sale').is(':visible')) {
                $('body').addClass('modal-open');
            }
        });
// modal-sale დაიხურა → template-დან inactive option გასუფთავება
$('#modal-sale').on('hidden.bs.modal', function() {
    $('#product-options-template option[data-inactive="1"]').remove();
    isEditMode = false;
});
// =====================
// Quick Status Change
// =====================
function openStatusModal(orderId, currentStatusId) {
    let allowedStatuses = [];
    
    if (currentStatusId == 1) allowedStatuses = [1, 2]; // ახალი -> გზაში
    if (currentStatusId == 2) allowedStatuses = [1, 2, 3]; // გზაში -> ახალი ან საწყობი
    if (currentStatusId == 3) allowedStatuses = [2, 3, 4]; // საწყობი -> გზაში ან კურიერი
    if (currentStatusId == 4) allowedStatuses = [3, 4]; // კურიერი -> საწყობი

    $('#statusSelect option').each(function() {
        let val = $(this).val();
        if (allowedStatuses.includes(parseInt(val))) {
            $(this).show().prop('disabled', false);
        } else {
            $(this).hide().prop('disabled', true);
        }
    });
    $('#status_order_id').val(orderId);
    $('#quick_status_select').val(currentStatusId);
    $('#modal-status').modal('show');
}
// ==========================================
// სურათის გადიდების (Lightbox) ლოგიკა
// ==========================================
$(document).on('click', '.img-zoom-trigger', function() {
    // 1. ავიღოთ სურათის მისამართი (src)
    var imgSrc = $(this).attr('src');
    
    // 2. შევამოწმოთ, რომ სურათი ნამდვილად არსებობს და არ არის "no-image" placeholder
    if (!imgSrc || imgSrc.includes('no-image') || imgSrc.includes('placeholder')) {
        return; 
    }

    // 3. ჩავსვათ მისამართი მოდალის სურათში
    $('#preview-img-full').attr('src', imgSrc);

    // 4. გავხსნათ მოდალი
    $('#modal-image-preview').modal('show');
});

// სურვილისამებრ: მოდალის დახურვისას სურათის გასუფთავება (მხოლოდ ვიზუალური სისუფთავისთვის)
$('#modal-image-preview').on('hidden.bs.modal', function () {
    $('#preview-img-full').attr('src', '');
});

function saveQuickStatus() {
    var id       = $('#status_order_id').val();
    var statusId = $('#quick_status_select').val();
    var csrf     = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ url('productsOut') }}/" + id + "/status",
        type: "POST",
        data: {
            _method:   'PATCH',
            _token:    csrf,
            status_id: statusId
        },
        success: function(data) {
            $('#modal-status').modal('hide');
            table.ajax.reload(null, false); // false = პაგინაცია არ ბრუნდება
            // პატარა toast შეტყობინება სwal-ის ნაცვლად
            var toast = $('<div>')
                .text('✓ სტატუსი განახლდა')
                .css({
                    position: 'fixed', bottom: '20px', right: '20px',
                    background: '#27ae60', color: '#fff',
                    padding: '10px 20px', borderRadius: '6px',
                    fontSize: '13px', fontWeight: '600',
                    zIndex: 9999, boxShadow: '0 4px 15px rgba(0,0,0,0.2)'
                })
                .appendTo('body');
            setTimeout(function() { toast.fadeOut(300, function() { $(this).remove(); }); }, 2000);
        },
        error: function() {
            swal("შეცდომა", "სტატუსი ვერ შეიცვალა", "error");
        }
    });
}

function exportFilteredPDF() {
    // DataTable-ის ამჟამად ხილული რიგებიდან ID-ების აღება
    var ids = [];
    table.rows({ search: 'applied' }).data().each(function(row) {
        ids.push(row.id);
    });

    if (ids.length === 0) {
        swal("ინფო", "გაფილტრული ორდერი არ მოიძებნა", "info");
        return;
    }

    // POST ფორმით გაგზავნა (GET-ზე URL ძალიან გრძელი შეიძლება გახდეს)
    var form = $('<form method="POST" action="{{ route('exportPDF.productOrderFiltered') }}" target="_blank">');
    form.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
    ids.forEach(function(id) {
        form.append('<input type="hidden" name="ids[]" value="' + id + '">');
    });
    $('body').append(form);
    form.submit();
    form.remove();
}

$(document).on('change', '#toggle-deleted', function() {
    reloadTableWithFilters();
});


// სტატუს ფილტრის dropdown
$(document).on('click', '#status-filter-btn', function(e) {
    e.stopPropagation();
    $('#po-status-dropdown').toggle();
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('#po-status-wrap').length) {
        $('#po-status-dropdown').hide();
    }
});

// სტატუსების მიხედვით ფილტრაცია
$(document).on('change', '.status-filter-check', function() {
    var selected = [];
    $('.status-filter-check:checked').each(function() {
        selected.push($(this).val());
    });

    if (selected.length === 0) {
        $('#status-filter-label').text('სტატუსი');
    } else {
        $('#status-filter-label').text(selected.length + ' მონიშნ.');
    }

    reloadTableWithFilters(); // ← ეს იყო პრობლემა, ძველი კოდი პირდაპირ URL-ს ადგენდა
});

$(document).on('change', '#toggle-show-deleted', function() {
    reloadTableWithFilters();
});
function reloadTableWithFilters() {
    mergeMode = false;
    $('#po-page-root').removeClass('po-merge-mode');

    var params = [];
    if ($('#toggle-deleted').is(':checked'))      params.push('debt_only=1');
    if ($('#toggle-show-deleted').is(':checked')) params.push('show_deleted=1');

    var selected = [];
    $('.status-filter-check:checked').each(function() { selected.push($(this).val()); });
    if (selected.length) params.push('statuses[]=' + selected.join('&statuses[]='));

    var dateFrom = $('#filter-date-from').val();
    var dateTo   = $('#filter-date-to').val();
    if (dateFrom) params.push('date_from=' + dateFrom);
    if (dateTo)   params.push('date_to='   + dateTo);

    table.ajax.url("{{ route('api.productsOut') }}?" + params.join('&')).load();
    loadPoStats();
}

// ── Period buttons ──────────────────────────────────────────────
function applyPeriod(period) {
    var from = '', to = '';
    var now  = new Date();
    var pad  = function(n) { return n < 10 ? '0' + n : '' + n; };
    var fmt  = function(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); };

    if (period === 'today') {
        from = to = fmt(now);
    } else if (period === 'week') {
        var mon = new Date(now); mon.setDate(now.getDate() - now.getDay() + 1);
        from = fmt(mon); to = fmt(now);
    } else if (period === 'month') {
        from = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-01';
        to   = fmt(now);
    }
    // 'all' and 'custom' — leave blank or let user pick

    $('#filter-date-from').val(from);
    $('#filter-date-to').val(to);
    reloadTableWithFilters();
}

$(document).on('click', '.po-pill[data-period]', function() {
    $('.po-pill[data-period]').removeClass('active');
    $(this).addClass('active');
    var period = $(this).data('period');
    if (period === 'custom') {
        $('#customDates').addClass('show');
    } else {
        $('#customDates').removeClass('show');
        applyPeriod(period);
    }
});

$(document).on('click', '#applyCustom', function() {
    reloadTableWithFilters();
});

// index.blade.php - სკრიპტების ბოლოს

function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    
    swal({
        title: 'ნამდვილად გსურთ აღდგენა?',
        type: 'info',
        showCancelButton: true,
        confirmButtonText: 'დიახ, აღადგინე!',
        cancelButtonText: 'გაუქმება'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/restore",
            type: "POST",
            data: {'_token': csrf_token},
            success: function(data) {
                table.ajax.reload(null, false); // ცხრილის განახლება პაგინაციის შენარჩუნებით
                swal("აღდგენილია!", data.message, "success");
            },
            error: function() {
                swal("შეცდომა", "აღდგენა ვერ მოხერხდა", "error");
            }
        });
    });
}
// =====================
// Mail Modal
// =====================
function openMailModal(orderId, customerId, email) {
    $('#mail_order_id').val(orderId);
    $('#mail_customer_id').val(customerId);
    $('#mail_original_email').val(email);
    $('#mail_email_input').val(email);
    $('#mail_subject').val('თქვენი შეკვეთის ინფორმაცია #' + orderId);
    $('#mail_body').val('');
    $('#modal-mail').modal('show');
}
// real-time შემოწმება
$(document).on('input', '#mail_email_input', function() {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var val = $(this).val().trim();
    if (val === '' || emailRegex.test(val)) {
        $(this).css('border-color', '');
        $('#btn-send-mail').prop('disabled', false);
    } else {
        $(this).css('border-color', 'red');
        $('#btn-send-mail').prop('disabled', true);
    }
});
function sendMail() {
    var orderId    = $('#mail_order_id').val();
    var customerId = $('#mail_customer_id').val();
    var email      = $('#mail_email_input').val().trim();
    var origEmail  = $('#mail_original_email').val().trim();
    var subject    = $('#mail_subject').val().trim();
    var body       = $('#mail_body').val().trim();

    // ფორმატის შემოწმება
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
        $('#mail_email_input').css('border-color', 'red');
        swal("შეცდომა", "გთხოვთ შეიყვანოთ email მისამართი", "error");
        return;
    }
    if (!emailRegex.test(email)) {
        $('#mail_email_input').css('border-color', 'red');
        swal("შეცდომა", "email მისამართის ფორმატი არასწორია", "error");
        return;
    }

    $('#mail_email_input').css('border-color', '');

    if (email !== origEmail) {
        swal({
            title: 'შევინახო მეილი?',
            text: 'email "' + email + '" შეინახოს ამ კლიენტისთვის?',
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'დიახ, შევინახო',
            cancelButtonText: 'მხოლოდ გავგზავნო'
        }).then(function(result) {
            doSendMail(orderId, customerId, email, subject, body, result.value === true);
        });
    } else {
        doSendMail(orderId, customerId, email, subject, body, false);
    }
}

function doSendMail(orderId, customerId, email, subject, body, saveEmail) {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var btn  = $('#btn-send-mail');

    // პატარა delay რომ browser-მა მოასწროს render
    btn.prop('disabled', true)
       .html('<i class="fa fa-spinner fa-spin"></i> იგზავნება...');

    setTimeout(function() {
        $.ajax({
            url: "{{ url('productsOut') }}/" + orderId + "/sendMail",
            type: "POST",
            data: {
                _token:      csrf,
                email:       email,
                subject:     subject,
                body:        body,
                save_email:  saveEmail ? 1 : 0,
                customer_id: customerId
            },
            success: function(data) {
                btn.prop('disabled', false)
                   .html('<i class="fa fa-paper-plane"></i> გაგზავნა');

                $('#modal-mail').modal('hide');
                if (saveEmail) {
                    $('#mail_original_email').val(email);
                    table.ajax.reload(null, false);
                }
                var toast = $('<div>')
                    .text('✓ მეილი გაიგზავნა')
                    .css({
                        position:'fixed', bottom:'20px', right:'20px',
                        background:'#27ae60', color:'#fff',
                        padding:'10px 20px', borderRadius:'6px',
                        fontSize:'13px', fontWeight:'600',
                        zIndex:9999, boxShadow:'0 4px 15px rgba(0,0,0,0.2)'
                    }).appendTo('body');
                setTimeout(function() { toast.fadeOut(300, function(){ $(this).remove(); }); }, 2500);
            },
            error: function(xhr) {
                btn.prop('disabled', false)
                   .html('<i class="fa fa-paper-plane"></i> გაგზავნა');

                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'მეილი ვერ გაიგზავნა';
                swal("შეცდომა", msg, "error");
            }
        });
    }, 50); // 50ms საკმარისია render-ისთვის
}
// =====================
// Quick Pay Modal
// =====================
function openPayModal(id, price, discount, tbc, bog, lib, cash) {
    document.getElementById('pay_order_id').value    = id;
    document.getElementById('pay_price_hidden').value = price || 0;
    document.getElementById('pay_price_display').textContent = parseFloat(price || 0).toFixed(2) + ' ₾';
    document.getElementById('pay_discount').value = discount || 0;
    document.getElementById('pay_tbc').value       = tbc  || 0;
    document.getElementById('pay_bog').value       = bog  || 0;
    document.getElementById('pay_lib').value       = lib  || 0;
    document.getElementById('pay_cash').value      = cash || 0;
    calcPaySummary();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-quick-pay')).show();
}

function calcPaySummary() {
    var price    = parseFloat(document.getElementById('pay_price_hidden').value || 0);
    var discount = parseFloat(document.getElementById('pay_discount').value || 0);
    var tbc      = parseFloat(document.getElementById('pay_tbc').value  || 0);
    var bog      = parseFloat(document.getElementById('pay_bog').value  || 0);
    var lib      = parseFloat(document.getElementById('pay_lib').value  || 0);
    var cash     = parseFloat(document.getElementById('pay_cash').value || 0);

    var total = price - discount;
    var paid  = tbc + bog + lib + cash;
    var diff  = paid - total;

    var el = document.getElementById('pay_summary');
    if (diff < -0.01) {
        el.style.background = '#fdecea';
        el.style.color      = '#c0392b';
        el.textContent      = 'აკლია: ' + Math.abs(diff).toFixed(2) + ' ₾  (გასასტუმრებელი: ' + total.toFixed(2) + ' ₾)';
    } else if (diff > 0.01) {
        el.style.background = '#e8f8f5';
        el.style.color      = '#1e8449';
        el.textContent      = 'ზედმეტია: ' + diff.toFixed(2) + ' ₾';
    } else {
        el.style.background = '#e8f8f5';
        el.style.color      = '#1e8449';
        el.textContent      = '✓ სრულად გადახდილია (' + total.toFixed(2) + ' ₾)';
    }
}

function savePayment() {
    var id   = document.getElementById('pay_order_id').value;
    var btn  = document.getElementById('btn-save-pay');
    btn.disabled = true;

    fetch('{{ url("productsOut") }}/' + id + '/payment', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept':       'application/json',
        },
        body: JSON.stringify({
            _method:   'PATCH',
            paid_tbc:  parseFloat(document.getElementById('pay_tbc').value  || 0),
            paid_bog:  parseFloat(document.getElementById('pay_bog').value  || 0),
            paid_lib:  parseFloat(document.getElementById('pay_lib').value  || 0),
            paid_cash: parseFloat(document.getElementById('pay_cash').value || 0),
            discount:  parseFloat(document.getElementById('pay_discount').value || 0),
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-quick-pay')).hide();
            table.ajax.reload(null, false);
        } else {
            alert(res.message || 'შეცდომა');
        }
    })
    .catch(function() { alert('სერვერის შეცდომა'); })
    .finally(function() { btn.disabled = false; });
}

function showStatusLog(orderId) {
    $('#status-log-body').html('<tr><td colspan="4" class="text-center">იტვირთება...</td></tr>');
    $('#modal-status-log').modal('show');

    $.get('/product-order/' + orderId + '/status-log', function(logs) {
        if (logs.length === 0) {
            $('#status-log-body').html('<tr><td colspan="4" class="text-center text-muted">ისტორია არ არის</td></tr>');
            return;
        }

        let html = '';
        logs.forEach(function(log) {
            const from = log.from_status
                ? '<span class="label label-' + log.from_status.color + '">' + log.from_status.name + '</span>'
                : '<span class="text-muted">—</span>';

            const to = '<span class="label label-' + log.to_status.color + '">' + log.to_status.name + '</span>';

            html += `<tr>
                <td>${log.changed_at}</td>
                <td>${from}</td>
                <td>${to}</td>
                <td>${log.user ? log.user.name : '—'}</td>
            </tr>`;
        });

        $('#status-log-body').html(html);
    });
}

// =====================
// Checkbox — ყველას მონიშვნა
// =====================
$(document).on('change', '#check-all', function() {
    $('.row-check').prop('checked', $(this).is(':checked'));
    toggleMergeBtn();
});

$(document).on('change', '.row-check', function() {
    toggleMergeBtn();
});

function toggleMergeBtn() {
    var checked = $('.row-check:checked');
    var count   = checked.length;

    if (count >= 1) {
        $('#po-bulk-count').text(count + ' მონიშნული');
        $('#po-bulk-bar').show();
    } else {
        $('#po-bulk-bar').hide();
    }

    if (count >= 2) {
        $('#btn-merge').show();
    } else {
        $('#btn-merge').hide();
    }
}

function clearPoSelection() {
    $('.row-check').prop('checked', false);
    $('#check-all').prop('checked', false);
    $('#po-bulk-bar').hide();
    $('#btn-merge').hide();
    if (mergeMode) {
        mergeMode = false;
        $('#po-page-root').removeClass('po-merge-mode');
        reloadTableWithFilters();
    }
}

// =====================
// Merge — გაერთიანება
// =====================
function mergeSelected() {
    var ids = [];
    $('.row-check:checked').each(function() {
        ids.push($(this).data('id'));
    });

    if (ids.length < 2) {
        swal("ინფო", "მინიმუმ 2 ორდერი აირჩიე", "info");
        return;
    }

    swal({
        title: 'გაერთიანება?',
        text: ids.length + ' ორდერი გაერთიანდება. პირველი (#' + ids[0] + ') იქნება მთავარი.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ, გავაერთიანო',
        cancelButtonText: 'გაუქმება'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut/merge') }}",
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                ids:    ids
            },
            success: function(data) {
                table.ajax.url("{{ route('api.productsOut') }}").load();
                $('#btn-merge').hide();
                $('#check-all').prop('checked', false);
                swal("წარმატება!", data.message, "success");
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "ვერ გაერთიანდა", "error");
            }
        });
    });
}

// =====================
// 🔍 Merge Search — customer-ის ყველა გასაერთიანებელი ორდერის ჩვენება
// =====================
$(document).on('click', '.merge-search-btn', function(e) {
    e.stopPropagation();
    var customerId = $(this).data('customer-id');

    mergeMode = true;
    $('#po-page-root').addClass('po-merge-mode');

    table.ajax.url(
        "{{ route('api.productsOut') }}?merge_customer_id=" + customerId
    ).load(function() {
        $('.row-check').prop('checked', true);
        toggleMergeBtn();
    });
});

// =====================
// Unmerge — გაყოფა
// =====================
function unmergeOrder(id) {
    swal({
        title: 'გაყოფა?',
        text: 'გაერთიანება გაუქმდება და ყველა ორდერი დამოუკიდებელი გახდება.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ',
        cancelButtonText: 'გაუქმება'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/unmerge",
            type: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                table.ajax.reload(null, false);
                swal("წარმატება!", data.message, "success");
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "ვერ გაიყო", "error");
            }
        });
    });
}

// =====================
// Split — ამ ორდერის გამოყოფა ჯგუფიდან
// =====================
function splitFromGroup(id) {
    swal({
        title: 'გამოყოფა?',
        text: 'ეს ორდერი გამოვა ჯგუფიდან და დამოუკიდებელი გახდება.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ',
        cancelButtonText: 'გაუქმება'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/split",
            type: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('tr.child-row-' + id).remove();
                table.ajax.reload(null, false);
                swal("წარმატება!", data.message, "success");
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "ვერ გამოიყო", "error");
            }
        });
    });
}

// =====================
// Expand / Collapse — შვილების გაშლა
// =====================
$(document).on('click', '.expand-btn', function() {
    var btn      = $(this);
    var parentId = btn.data('id');
    var allOrders = (window._childrenStore || {})[parentId] || [];
    var parentRow = btn.closest('tr');

    if (btn.hasClass('open')) {
        btn.removeClass('open');
        $('tr.child-row-' + parentId).remove();
        return;
    }
    btn.addClass('open');
    if (!allOrders || allOrders.length === 0) return;

    var totalCols = columns.length;
    var rowsHtml  = '';

    allOrders.forEach(function(order) {
        var orderNo = order.order_number || ('S'+order.id);

        // Col A: order num + status + comment
        var commentHtml = order.comment
            ? '<div style="margin-top:4px;"><small style="color:#1a5276;background:#eaf4fb;border-radius:3px;padding:1px 5px;display:inline-block;font-size:10px;"><i class="fa fa-comment" style="font-size:9px;"></i> ' + $('<div>').text(order.comment).html() + '</small></div>'
            : '';
        var colA = '<span class="po-order-num" style="font-size:11px;">' + orderNo + '</span>'
            + '<div style="margin-top:4px;"><span class="label label-' + order.status_color + '" style="font-size:10px;">' + order.status_name + '</span></div>'
            + commentHtml;

        // Col B: product
        var imgHtml = order.product_image
            ? '<div class="po-product-thumb" style="width:36px;height:36px;"><img src="' + order.product_image + '"></div>'
            : '';
        var colB = '<div class="po-product-cell">' + imgHtml
            + '<div style="font-size:12px;"><div style="font-weight:600;">' + (order.product_name || '') + '</div>'
            + (order.product_size ? '<span class="label label-info" style="font-size:10px;">' + order.product_size + '</span>' : '')
            + '</div></div>';

        // Col C: finance
        var chGeo  = parseFloat(order.price_georgia || 0) - parseFloat(order.discount || 0);
        var chPaid = parseFloat(order.paid_tbc || 0) + parseFloat(order.paid_bog || 0)
                   + parseFloat(order.paid_lib || 0) + parseFloat(order.paid_cash || 0);
        var chIsPaid = (chGeo - chPaid) <= 0.01;
        var chPct    = chGeo > 0 ? Math.min(chPaid/chGeo, 1) : 1;
        var chTag = chIsPaid
            ? '<span class="po-paid-tag"><i class="fa fa-check" style="font-size:9px;"></i> გადახდილი</span>'
            : '<span class="po-debt-tag">' + (chGeo-chPaid).toFixed(2) + ' ₾</span>';
        var colC = '<div class="po-finance-total" style="font-size:13px;">' + chGeo.toFixed(2) + ' ₾</div>'
            + '<div style="margin-top:3px;">' + chTag + '</div>';

        // Col D: actions
        var chPayClass = chIsPaid ? 'po-pay-paid' : (chPct > 0 ? 'po-pay-partial' : 'po-pay-debt');
        var chPayBtn = '';
        if (!isSaleOperator) {
            chPayBtn = '<a onclick="openPayModal(' + order.id + ',' + (order.price_georgia||0) + ',' + (order.discount||0) + ',' + (order.paid_tbc||0) + ',' + (order.paid_bog||0) + ',' + (order.paid_lib||0) + ',' + (order.paid_cash||0) + ')" class="btn btn-xs ' + chPayClass + '" title="გადახდა"><i class="fa fa-credit-card"></i></a>';
        }
        var splitBtn = '<a onclick="splitFromGroup(' + order.id + ')" class="btn btn-xs btn-warning" title="ჯგუფიდან გამოყოფა"><i class="fa fa-scissors"></i></a>';
        var colD = '<div class="po-actions">' + chPayBtn + splitBtn;
        if (isAdmin) {
            var canDel  = order.status_id != 4;
            colD += '<a onclick="editForm(' + order.id + ')" class="btn btn-xs btn-primary" title="რედაქტირება"><i class="fa fa-pen"></i></a>';
            colD += canDel
                ? '<a onclick="deleteData(' + order.id + ')" class="btn btn-xs btn-danger" title="წაშლა"><i class="fa fa-trash"></i></a>'
                : '<span class="btn btn-xs btn-danger" style="opacity:.35;cursor:not-allowed;"><i class="fa fa-trash"></i></span>';
            colD += '<a onclick="showStatusLog(' + order.id + ')" class="btn btn-xs btn-warning" title="ისტორია"><i class="fa fa-clock-rotate-left"></i></a>';
            if (order.export_pdf_url) {
                colD += '<a href="' + order.export_pdf_url + '" target="_blank" class="btn btn-xs btn-info" title="PDF"><i class="fa fa-file-pdf"></i></a>';
            }
        }
        colD += '</div>';

        // Sub-row
        rowsHtml += '<tr class="child-row-' + parentId + ' po-child-row">'
            + '<td colspan="' + totalCols + '" style="padding:8px 14px 8px 48px;">'
            + '<div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">'
            + '<div style="min-width:120px;">' + colA + '</div>'
            + '<div style="flex:1;min-width:150px;">' + colB + '</div>'
            + '<div style="min-width:100px;">' + colC + '</div>'
            + '<div>' + colD + '</div>'
            + '</div></td></tr>';
    });

    parentRow.after(rowsHtml);
});

// table reload-ისას გაშლილი სტრიქონები გაქრება — ეს ნორმალურია
table.on('draw', function() {
    $('#check-all').prop('checked', false);
    $('#btn-merge').hide();
    $('#po-bulk-bar').hide();
});

// =====================
// Merge სტატუს განახლება (primary → ყველა შვილი id=4)
// =====================
function mergeUpdateStatus(primaryId, mergedId) {
    swal({
        title: 'კურიერთან გაგზავნა?',
        text: 'ყველა დაჯგუფებული ორდერი გადავა "კურიერთან" სტატუსში.',
        type: 'question',
        showCancelButton: true,
        confirmButtonText: 'დიახ',
        cancelButtonText: 'გაუქმება'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut/mergeStatus') }}",
            type: "POST",
            data: {
                _token:    $('meta[name="csrf-token"]').attr('content'),
                merged_id: mergedId,
                status_id: 4
            },
            success: function(data) {
                table.ajax.reload(null, false);

                var pdfUrl = "{{ url('exportProductOrder') }}/" + primaryId;

                swal({
                    title: '✅ კურიერს გადაეცა!',
                    type: 'success',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'დახურვა',
                    html: 'გსურთ ორდერის დაბეჭდვა?<br><br>' +
                          '<a href="' + pdfUrl + '" target="_blank" ' +
                          'class="btn btn-success" ' +
                          'onclick="swal.close()">' +
                          '<i class="fa fa-print"></i> დაბეჭდვა' +
                          '</a>'
                });
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "შეცდომა", "error");
            }
        });
    });
}

// როცა მომხმარებელი აჭერს Save-ს
// $('#form-sale-content').on('submit', function() {
//     // დროებით ვააქტიურებთ დაბლოკილ ველებს გაგზავნისთვის
//     $(this).find(':disabled').prop('disabled', false);
// });

window.sendSingleToCourier = function(id) {
    swal({
        title: 'კურიერთან გაგზავნა?',
        text: 'ორდერი #' + id + ' კურიერს გადაეცემა',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00a65a',
        cancelButtonText: 'გაუქმება',
        confirmButtonText: 'დიახ, გაგზავნა!'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/send-to-courier",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
    table.ajax.reload();

    var pdfUrl = "{{ url('exportProductOrder') }}/" + id;

    swal({
        title: '✅ კურიერს გადაეცა!',
        text: 'გსურთ ორდერის დაბეჭდვა?',
        type: 'success',
        showCancelButton: true,
        cancelButtonText: 'არა',
        confirmButtonText: 'დაბეჭდვა',
        // ── confirm ღილაკის ნაცვლად HTML link ──
        html: 'გსურთ ორდერის დაბეჭდვა?<br><br>' +
              '<a href="' + pdfUrl + '" target="_blank" ' +
              'class="btn btn-success" ' +
              'onclick="swal.close()">' +
              '<i class="fa fa-print"></i> დაბეჭდვა' +
              '</a>',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'დახურვა'
    });
},
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა!';
                swal('შეცდომა', msg, 'error');
            }
        });
    });
};

window.revertFromCourier = function(id) {
    swal({
        title: 'საწყობში დაბრუნება?',
        text: 'ორდერი კურიერს ჩამოერთმევა და სტატუსი "საწყობში" დაუბრუნდება',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonText: 'გაუქმება',
        confirmButtonText: 'დიახ, დაბრუნება!'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/revert-from-courier",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
                table.ajax.reload(null, false);
                swal('✅ დაბრუნდა!', res.message, 'success');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა!';
                swal('შეცდომა', msg, 'error');
            }
        });
    });
};

// ════════════════════════════════════════════════════════════
// 🔄 CHANGE ORDER JS
// ════════════════════════════════════════════════════════════

window.openChangeModal = function(saleId) {
    $('#form-change')[0].reset();
    $('#change_original_sale_id').val(saleId);
    $('input[name="change_type"][value="return"]').prop('checked', true);
    $('#change-stock-info').hide();
    $('#change-price-diff-block').hide();
    $('#change-product-group').hide();
    $('#change_size').empty().append('<option value="">— ზომა —</option>');

    $.get("{{ url('productsOut') }}/" + saleId + "/edit", function(data) {
        $('#change-orig-id').text(data.id);
        $('#change-orig-product').text(data.current_product ? data.current_product.name : '');
        $('#change-orig-size').text(data.product_size || '');
        $('#change_product_id').val(data.product_id);
        $('#form-change').data('orig-price', parseFloat(data.price_georgia) || 0);
        $('#form-change').data('orig-product-id', data.product_id);
        $('#form-change').data('orig-size', data.product_size);

        var sizes = $('#change_product_id option[value="' + data.product_id + '"]').data('sizes') || '';
        // return არის default — ზომა ჩავავსოთ და ჩავკეტოთ
        var $sel = $('#change_size');
        $sel.empty().prop('disabled', true);
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $sel.val(data.product_size);
    });

    $('#modal-change').modal('show');
};

function populateChangeSizes(sizesRaw, selectedSize) {
    var $sel = $('#change_size');
    $sel.empty().append('<option value="">— ზომა —</option>');
    if (sizesRaw) {
        sizesRaw.toString().split(',').forEach(function(s) {
            s = s.trim();
            if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
        });
    }
    if (selectedSize) {
        $sel.val(selectedSize);
        loadChangeStockInfo();
    }
    $('#change-stock-info').hide();
}

function loadChangeStockInfo() {
    var changeType = $('input[name="change_type"]:checked').val();
    if (changeType === 'return') { $('#change-stock-info').hide(); return; }
    var prodId = $('#change_product_id').val();
    var size   = $('#change_size').val();
    if (!prodId || !size) { $('#change-stock-info').hide(); return; }

    $.get("{{ route('warehouse.stockInfo') }}", { product_id: prodId, size: size }, function(data) {
        if (!data.found) {
            $('#chg-si-physical, #chg-si-incoming, #chg-si-reserved, #chg-si-available').text(0);
            $('#chg-si-available').css('color', '#e74c3c');
            $('#chg-si-badge').html('<span class="label label-danger">ნაშთი არ არის — მოლოდინში წავა</span>');
        } else {
            var avail = data.available;
            $('#chg-si-physical').text(data.physical_qty);
            $('#chg-si-incoming').text(data.incoming_qty);
            $('#chg-si-reserved').text(data.reserved_qty);
            $('#chg-si-available').text(avail);
            var color = avail <= 0 ? '#e74c3c' : (avail <= 3 ? '#f39c12' : '#00a65a');
            var badge = avail <= 0
                ? '<span class="label label-danger">ნაშთი არ არის</span>'
                : (avail <= 3 ? '<span class="label label-warning">მცირე ნაშთი</span>'
                              : '<span class="label label-success">ხელმისაწვდომია</span>');
            $('#chg-si-available').css('color', color);
            $('#chg-si-badge').html(badge);
        }
        $('#change-stock-info').show();
    });
}

function updateChangePriceDiff() {
    var changeType = $('input[name="change_type"]:checked').val();
    if (changeType === 'return') { $('#change-price-diff-block').hide(); return; }
    var origPrice = parseFloat($('#form-change').data('orig-price') || 0);
    var newPrice  = parseFloat($('#change_product_id option:selected').data('price-ge') || 0);
    var diff = newPrice - origPrice;
    var diffEl = $('#change-price-diff');
    if (Math.abs(diff) < 0.01) {
        diffEl.text('სხვაობა არ არის').css('color', '#888');
    } else if (diff > 0) {
        diffEl.text('+' + diff.toFixed(2) + ' ₾ (კლიენტმა უნდა გადაიხადოს)').css('color', '#e74c3c');
    } else {
        diffEl.text(diff.toFixed(2) + ' ₾ (სასარგებლოდ)').css('color', '#27ae60');
    }
    $('#change-price-diff-block').show();
}

$(document).on('change', 'input[name="change_type"]', function() {
    var type = $(this).val();
    var origProductId = $('#form-change').data('orig-product-id');
    var origSize      = $('#form-change').data('orig-size');

    // კურიერის შენიშვნა ტიპის მიხედვით
    var note = type === 'return'
        ? '↩ დაბრუნებისას — კურიერი შესყიდვაზე ჩაიწერება'
        : '🔄 გაცვლისას — კურიერი ახალ sale ორდერზე ჩაიწერება';
    $('#change-courier-note').text(note);

    if (type === 'return') {
        // დაბრუნება — product + ზომა ავტომატური, ჩაკეტილი
        $('#change-product-group').hide();
        $('#change-price-diff-block').hide();
        $('#change-stock-info').hide();
        $('#change_product_id').val(origProductId);
        var sizes = $('#change_product_id option[value="' + origProductId + '"]').data('sizes') || '';
        var $sel = $('#change_size');
        $sel.empty();
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $sel.val(origSize).prop('disabled', true); // ჩაკეტვა
    } else if (type === 'size') {
        // ზომის გაცვლა — product იგივე, ზომა სხვა (original გამოვრიცხოთ)
        $('#change-product-group').hide();
        $('#change_product_id').val(origProductId);
        var opt = $('#change_product_id option[value="' + origProductId + '"]');
        var $sel2 = $('#change_size');
        $sel2.empty().append('<option value="">— ზომა —</option>').prop('disabled', false);
        var sizes2 = opt.data('sizes') || '';
        if (sizes2) {
            sizes2.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s && s !== origSize) { // original ზომა გამოვრიცხოთ
                    $sel2.append('<option value="' + s + '">' + s + '</option>');
                }
            });
        }
        $('#change-stock-info').hide();
        updateChangePriceDiff();
    } else {
        // პროდუქტის გაცვლა
        $('#change-product-group').show();
        $('#change_product_id').val('');
        $('#change_size').empty().append('<option value="">— ზომა —</option>').prop('disabled', false);
        $('#change-stock-info').hide();
        updateChangePriceDiff();
    }
});

$(document).on('change', '#change_product_id', function() {
    var sizes = $(this).find('option:selected').data('sizes') || '';
    populateChangeSizes(sizes, null);
    $('#change-stock-info').hide();
    updateChangePriceDiff();
});

$(document).on('change', '#change_size', function() {
    loadChangeStockInfo();
    updateChangePriceDiff();
});

$('#form-change').on('submit', function(e) {
    e.preventDefault();
    var productId = $('#change_product_id').val();
    var size      = $('#change_size').val();
    if (!productId || !size) {
        swal('შეცდომა', 'პროდუქტი და ზომა სავალდებულოა', 'error');
        return;
    }
    // disabled ველები serialize-ში არ ჩაერთვება — დროებით გავხსნოთ
    var $disabledSize = $('#change_size').filter(':disabled');
    $disabledSize.prop('disabled', false);
    var formData = $(this).serialize();
    $disabledSize.prop('disabled', true);

    $.ajax({
        url:  "{{ url('productsOut/change') }}",
        type: 'POST',
        data: formData,
        success: function(res) {
            $('#modal-change').modal('hide');
            table.ajax.reload(null, false);
            swal({ title: '✅', text: res.message, type: 'success', timer: 2000 });
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
            swal('შეცდომა', msg, 'error');
        }
    });
});

$('#modal-change').on('hidden.bs.modal', function() {
    $('#form-change')[0].reset();
    $('#change-price-diff-block').hide();
    $('#change-product-group').hide();
    $('#change-stock-info').hide();
    $('#change_size').prop('disabled', false);
});

// =====================
// Dark mode toggle
// =====================
function togglePoTheme() {
    var $root = $('#po-page-root');
    var isDark = $root.toggleClass('po-dark').hasClass('po-dark');
    $('#po-theme-icon').toggleClass('fa-moon', !isDark).toggleClass('fa-sun', isDark);
    try { localStorage.setItem('po-theme', isDark ? 'dark' : 'light'); } catch(e) {}
}
(function() {
    try {
        if (localStorage.getItem('po-theme') === 'dark') {
            $('#po-page-root').addClass('po-dark');
            $('#po-theme-icon').removeClass('fa-moon').addClass('fa-sun');
        }
    } catch(e) {}
})();

// =====================
// Export dropdown
// =====================
$('#po-export-btn').on('click', function(e) {
    e.stopPropagation();
    var $menu = $('#po-export-menu');
    $menu.toggle();
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('#po-export-wrap').length) {
        $('#po-export-menu').hide();
    }
});

// =====================
// Stats strip loader
// =====================
function loadPoStats() {
    var selected = [];
    $('.status-filter-check:checked').each(function() { selected.push($(this).val()); });

    // build data the same way reloadTableWithFilters builds the URL
    var data = {
        date_from: $('#filter-date-from').val(),
        date_to:   $('#filter-date-to').val()
    };
    if (selected.length)                           data['statuses[]']  = selected;
    if ($('#toggle-deleted').is(':checked'))        data.debt_only      = 1;
    if ($('#toggle-show-deleted').is(':checked'))   data.show_deleted   = 1;

    $.ajax({
        url: "{{ route('productsOut.stats') }}",
        type: 'GET',
        data: data,
        success: function(d) {
            $('#stat-total').text(d.total || 0);
            $('#stat-debt').text(d.debt   ? parseFloat(d.debt).toFixed(2)   + ' ₾' : '0 ₾');
            $('#stat-debt-sub').text(d.debt_count ? d.debt_count + ' ორდერი' : '—');
            $('#stat-paid').text(d.paid   ? parseFloat(d.paid).toFixed(2)   + ' ₾' : '0 ₾');
            $('#stat-courier').text(d.courier || 0);
        }
    });
}
loadPoStats();

    </script>
@endsection