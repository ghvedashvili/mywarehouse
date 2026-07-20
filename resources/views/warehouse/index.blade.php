@extends('layouts.master')
@section('page_title')<i class="fa fa-warehouse me-2" style="color:#8e44ad;"></i>საწყობი@endsection

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
/* ── Design tokens ── */
:root {
    --wh-surface:    #ffffff;
    --wh-surface2:   #f6f7fb;
    --wh-border:     rgba(99,115,150,.16);
    --wh-border-md:  rgba(99,115,150,.24);
    --wh-text-1:     #1a2235;
    --wh-text-2:     #3d4a5c;
    --wh-text-3:     #7a8a9e;
    --wh-green:      #059669;
    --wh-orange:     #d97706;
    --wh-red:        #dc2626;
    --wh-blue:       #2563eb;
    --wh-purple:     #7c3aed;
    --wh-dark:       #1a2235;
    --wh-r-md:       12px;
    --wh-sh-sm:      0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03);
}

/* ── Mobile search bar: hidden on desktop ── */
.wh-mob-search-wrap  { display: none; }
.wh-mob-action-bar   { display: none; }

/* ── Stat cards ── */
.stat-card {
    background: var(--wh-surface);
    border: 1px solid var(--wh-border-md);
    border-radius: var(--wh-r-md);
    padding: 14px 18px;
    border-left: 4px solid var(--wh-green);
    box-shadow: var(--wh-sh-sm);
    height: 100%;
}
.stat-card.orange { border-left-color: var(--wh-orange); }
.stat-card.blue   { border-left-color: var(--wh-blue); }
.stat-card.purple { border-left-color: var(--wh-purple); }
.stat-card.red    { border-left-color: var(--wh-red); }
.stat-card .val   { font-size: 28px; font-weight: 800; color: var(--wh-dark); line-height: 1; }
.stat-card .lbl   { font-size: 11px; color: var(--wh-text-3); text-transform: uppercase; letter-spacing: 0.6px; margin-top: 5px; }

/* ── Table header ── */
.wh-table thead th {
    background: var(--wh-surface2);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--wh-text-3);
    border-bottom: 1.5px solid var(--wh-border-md) !important;
    white-space: nowrap;
}

/* ── Quantity badges ── */
.qty-badge {
    display: inline-block;
    min-width: 34px;
    text-align: center;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 13px;
}
.qty-physical        { background: #d1fae5; color: #065f46; }
.qty-incoming        { background: #dbeafe; color: #1e40af; }
.qty-return-incoming { background: #ede9fe; color: #5b21b6; }
.qty-reserved        { background: #fef3c7; color: #92400e; }
.qty-available       { background: var(--wh-dark); color: #fff; }
.qty-zero            { background: #fee2e2; color: #991b1b; }

/* ── Financial bar ── */
.fin-bar {
    display: flex; align-items: center; flex-wrap: wrap; gap: 0;
    background: var(--wh-surface);
    border: 1px solid var(--wh-border-md);
    border-radius: var(--wh-r-md);
    padding: 12px 20px;
    box-shadow: var(--wh-sh-sm);
}
.fin-item  { display: flex; align-items: center; gap: 10px; flex: 1 1 0; min-width: 160px; padding: 4px 8px; }
.fin-icon  { font-size: 22px; line-height: 1; }
.fin-val   { font-size: 18px; font-weight: 800; color: var(--wh-dark); line-height: 1.1; }
.fin-lbl   { font-size: 10px; color: var(--wh-text-3); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.fin-sep   { width: 1px; height: 40px; background: var(--wh-border-md); margin: 0 4px; flex-shrink: 0; }

/* ── Responsive expand control ── */
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
    background-color: var(--wh-green);
    border-radius: 50%;
}

/* ── Bulk bar ── */
#wh-bulk-bar {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: #1e293b; color: #fff; border-radius: 14px;
    padding: 10px 18px; display: none; align-items: center; gap: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,.3); z-index: 9999; white-space: nowrap;
}
#wh-bulk-bar.show { display: flex; }
#wh-bulk-bar .bulk-count { font-size: 13px; font-weight: 600; }
#wh-bulk-bar button { border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; }
.wh-bulk-clear { background: transparent; color: #94a3b8; font-size: 18px; padding: 0 4px !important; }

/* ══════════════════════════════════════════════
   MOBILE CARD VIEW  ≤767px
══════════════════════════════════════════════ */
@media (max-width: 767px) {

    /* ── Override master.blade.php global card layout for this table ── */
    #stock-table                            { display: table !important; width: 100% !important; min-width: 700px; }
    #stock-table thead                      { display: table-header-group !important; }
    #stock-table tbody                      { display: table-row-group !important; }
    #stock-table tbody tr                   { display: table-row !important; background: transparent !important; border-radius: 0 !important; margin: 0 !important; box-shadow: none !important; overflow: visible; }
    #stock-table tbody td                   { display: table-cell !important; padding: 6px 8px !important; border: 1px solid #dee2e6 !important; min-height: auto; justify-content: initial; }
    #stock-table tbody td::before           { display: none !important; }
    .mod-card .table-responsive             { overflow-x: auto !important; }

    /* Mobile: thead shows icons only */
    #stock-table thead th .th-txt { display: none; }
    #stock-table thead th { white-space: nowrap; text-align: center; font-size: 15px; padding: 6px 6px !important; }

    /* Hide desktop chrome */
    .mod-header  { display: none !important; }
    .mod-toolbar { display: none !important; }

    /* Page padding: leave room for bulk bar */
    .mod-wrap { padding: 8px 12px 80px !important; }

    /* Strip card styling from mod-card */
    .mod-card {
        background: transparent !important;
        border: none !important; box-shadow: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }
    .mod-card .table-responsive { overflow-x: auto !important; }

    /* Stat cards: compact */
    .stat-card { padding: 10px 12px; }
    .stat-card .val { font-size: 22px; }

    /* Fin-bar: compact + stack separators */
    .fin-bar  { padding: 8px 10px; }
    .fin-item { min-width: 110px; padding: 5px 6px; }
    .fin-val  { font-size: 15px; }
    .fin-sep  { display: none; }

    /* ── Mobile action bar ── */
    .wh-mob-action-bar {
        display: flex; flex-wrap: nowrap; align-items: center; gap: 6px;
        padding: 0 0 10px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .wh-mob-action-bar::-webkit-scrollbar { display: none; }

    /* ── Mobile search bar ── */
    .wh-mob-search-wrap { display: block; margin-bottom: 10px; position: relative; }
    .wh-mob-search {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid var(--wh-border-md);
        border-radius: 14px; padding: 10px 6px 10px 14px;
        box-shadow: var(--wh-sh-sm);
    }
    .wh-mob-search > i { font-size: 13px; color: var(--wh-text-3); flex-shrink: 0; }
    .wh-mob-search input {
        flex: 1; min-width: 0; background: none; border: none; outline: none;
        font-size: 14px; color: var(--wh-text-1); font-family: inherit;
    }
    .wh-mob-search input::placeholder { color: var(--wh-text-3); font-size: 13px; }
    #wh-filter-btn.has-active .mob-ts-filter-badge { display: flex !important; }

    /* ── Filter drawer panel ── */
    @keyframes whPanelIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .wh-filter-panel {
        display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid var(--wh-border-md);
        border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.12);
        z-index: 200; overflow: hidden;
    }
    .wh-filter-panel.open { display: block; animation: whPanelIn .18s ease; }
    .wh-filter-panel-body { display: flex; flex-direction: column; padding: 12px 16px 14px; gap: 10px; }
    .wh-fp-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 3px; }
    .wh-filter-panel select {
        width: 100%; height: 40px; font-size: 13.5px;
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0 10px;
        background: #fff; color: #1e293b; font-family: inherit;
    }

    /* Action bar buttons */
    .wh-mab-btn {
        display: inline-flex; align-items: center; gap: 5px;
        white-space: nowrap; cursor: pointer; font-family: inherit;
        font-size: 12.5px; font-weight: 600; line-height: 1;
        padding: 7px 13px; border-radius: 22px; border: 1.5px solid transparent;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
        transition: background .15s, transform .1s; flex-shrink: 0; text-decoration: none;
    }
    .wh-mab-btn:active     { transform: scale(.93); }
    .wh-mab-btn > i        { font-size: 10px; }
    .wh-mab-primary { background: var(--wh-blue);   border-color: var(--wh-blue);   color: #fff; box-shadow: 0 2px 10px rgba(37,99,235,.28); }
    .wh-mab-orange  { background: var(--wh-orange);  border-color: var(--wh-orange);  color: #fff; }
    .wh-mab-ghost   { background: var(--wh-surface); border-color: var(--wh-border-md); color: var(--wh-text-2); }
    .wh-mab-sep     { width: 1px; height: 20px; background: var(--wh-border-md); flex-shrink: 0; }

}

/* ══ STICKY FIRST COLUMN ══ */
.wh-sc-inner {
    display: flex; align-items: center; gap: 8px;
    min-width: 200px; max-width: 260px;
}
.wh-sc-cb  { flex: 0 0 auto; }
.wh-sc-text { flex: 1; min-width: 0; }
.wh-sc-name {
    font-weight: 600; font-size: 13px; line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 170px;
}
.wh-sc-code { font-size: 11px; color: #64748b; white-space: nowrap; margin-top: 2px; }
.wh-sc-size { flex: 0 0 auto; font-size: 11px; font-weight: 700; color: #475569; background: #f1f5f9; border-radius: 6px; padding: 2px 7px; white-space: nowrap; align-self: center; }

#stock-table thead th:first-child,
#stock-table tbody td:first-child {
    position: sticky; left: 0; z-index: 2;
    background: #fff;
    box-shadow: 2px 0 8px rgba(0,0,0,.09);
}
#stock-table thead th:first-child { z-index: 3; background: #f8fafc; }

/* When table is scrolled — collapse first column to image only */
.wh-tbl-scrolled #stock-table thead th:first-child,
.wh-tbl-scrolled #stock-table tbody td:first-child {
    box-shadow: 3px 0 14px rgba(0,0,0,.14);
}
.wh-tbl-scrolled .wh-sc-text,
.wh-tbl-scrolled .wh-sc-lbl { display: none !important; }
.wh-tbl-scrolled .wh-sc-inner { min-width: 0; max-width: none; gap: 6px; }
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-warehouse me-2" style="color:#8e44ad;"></i>საწყობი</h2>
            <p class="mod-subtitle">ნაშთების მართვა და კონტროლი</p>
        </div>
        @if(!in_array(auth()->user()->role, ['sale_operator', 'warehouse_operator']))
        <div class="mod-actions">
            <button class="btn btn-warning btn-sm" onclick="openWriteOffModal()">
                <i class="fa fa-minus-circle me-1"></i><span class="d-none d-sm-inline"> ჩამოწერა</span>
            </button>
            <a href="{{ route('warehouse.logs') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-history me-1"></i><span class="d-none d-sm-inline"> ლოგი</span>
            </a>
            <a href="{{ url('purchases') }}" class="btn btn-info btn-sm">
                <i class="fa fa-cart-shopping me-1"></i><span class="d-none d-sm-inline"> შესყიდვები</span>
            </a>
        </div>
        @endif
    </div>

    {{-- Mobile action bar --}}
    <div class="wh-mob-action-bar">
        @if(!in_array(auth()->user()->role, ['sale_operator', 'warehouse_operator']))
        <button class="wh-mab-btn wh-mab-orange" onclick="openWriteOffModal()">
            <i class="fa fa-minus-circle"></i> ჩამოწერა
        </button>
        <a href="{{ route('warehouse.logs') }}" class="wh-mab-btn wh-mab-ghost">
            <i class="fa fa-history"></i> ლოგი
        </a>
        <a href="{{ url('purchases') }}" class="wh-mab-btn wh-mab-ghost">
            <i class="fa fa-cart-shopping"></i> შესყიდვები
        </a>
        @endif
    </div>

    {{-- Stat cards --}}
    <div class="row g-2 mb-2">
        <div class="col-6 col-md">
            <div class="stat-card"><div class="val" id="stat-physical">—</div><div class="lbl">📦 ფიზიკური ნაშთი</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card orange"><div class="val" id="stat-incoming">—</div><div class="lbl">🚚 გზაში</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card purple"><div class="val" id="stat-return-incoming">—</div><div class="lbl">↩ დაბრუნება გზაში</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card blue"><div class="val" id="stat-reserved">—</div><div class="lbl">🔒 დაჯავშნული</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card red"><div class="val" id="stat-low">—</div><div class="lbl">⚠️ მცირე ნაშთი</div></div>
        </div>
    </div>

    {{-- Financial summary bar (admin only) --}}
    @if(auth()->user()->role == 'admin')
    <div id="fin-bar" class="fin-bar mb-3">
        <div class="fin-item">
            <span class="fin-icon">✅</span>
            <div>
                <div class="fin-val" id="fin-available">—</div>
                <div class="fin-lbl">ხელმისაწვდომი ნაშთი</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">💵</span>
            <div>
                <div class="fin-val" id="fin-cost">—</div>
                <div class="fin-lbl">ჯამური თვითღირებულება</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">📈</span>
            <div>
                <div class="fin-val" id="fin-revenue">—</div>
                <div class="fin-lbl">მოსალოდნელი შემოსავალი</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">💰</span>
            <div>
                <div class="fin-val" id="fin-profit">—</div>
                <div class="fin-lbl">მოსალოდნელი მოგება</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Mobile search bar + filter drawer --}}
    <div class="wh-mob-search-wrap">
        <div class="wh-mob-search">
            <i class="fa fa-search"></i>
            <input type="search" id="mob-wh-search" placeholder="ძებნა..." autocomplete="off">
            <button type="button" class="mob-ts-filter-btn" id="wh-filter-btn" onclick="toggleWhDrawer()">
                <i class="fa fa-sliders"></i>
                <span class="mob-ts-filter-badge" id="wh-filter-badge"></span>
            </button>
        </div>
        <div class="wh-filter-panel" id="wh-filter-drawer">
            <div class="wh-filter-panel-body">
                <div>
                    <div class="wh-fp-label">კატეგორია</div>
                    <select id="mob-filter-category">
                        <option value="">ყველა კატეგორია</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="wh-fp-label">ზომა</div>
                    <select id="mob-filter-size">
                        <option value="">ყველა ზომა</option>
                        @foreach($sizes as $size)
                            <option value="{{ $size }}">{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="wh-fp-label">ჩანაწ. რაოდ.</div>
                    <select id="mob-dt-page-length">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">ყველა</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-toolbar">
            <select id="dt-page-length" class="form-select form-select-sm" style="width:75px;">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="-1">ყველა</option>
            </select>
            <div class="mod-toolbar-search">
                <i class="fa fa-search search-icon"></i>
                <input id="dt-search" type="search" class="form-control form-control-sm" placeholder="ძებნა...">
            </div>
            <select id="filter-category" class="form-select form-select-sm" style="width:170px;">
                <option value="">ყველა კატეგორია</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select id="filter-size" class="form-select form-select-sm" style="width:180px;" multiple>
                @foreach($sizes as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="table-responsive">
            <table id="stock-table" class="table wh-table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th><div class="wh-sc-inner"><span class="wh-sc-cb"><input type="checkbox" id="wh-select-all" title="ყველა"></span><span class="wh-sc-lbl" style="font-weight:600;">პროდუქტი</span></div></th>
                        <th></th><th></th><th></th><th></th>
                        <th><span class="th-icon">📦</span><span class="th-txt"> ფიზ.</span></th>
                        <th><span class="th-icon">🚚</span><span class="th-txt"> გზაში</span></th>
                        <th><span class="th-icon">↩</span><span class="th-txt"> დაბრ.</span></th>
                        <th><span class="th-icon">🔒</span><span class="th-txt"> დაჯავშნ.</span></th>
                        <th><span class="th-icon">✅</span><span class="th-txt"> ხელმისაწვდ.</span></th>
                        <th><span class="th-icon">🧮</span><span class="th-txt"> FIFO</span></th>
                        <th><span class="th-icon">⚡</span><span class="th-txt"> სტატუსი</span></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap --}}

{{-- BULK ACTION BAR --}}
<div id="wh-bulk-bar">
    <span class="bulk-count"><span id="wh-bulk-count">0</span> მონიშნული</span>
    <button onclick="whBulkMessenger()" style="background:#0099ff;color:#fff;">
        <i class="fab fa-facebook-messenger me-1"></i> Messenger
    </button>
    <button onclick="whBulkCopy()" style="background:#475569;color:#fff;">
        <i class="fa fa-copy me-1"></i> კოპირება
    </button>
    <button onclick="whClearSelection()" class="wh-bulk-clear">✕</button>
</div>

{{-- Share Queue Modal --}}
<div class="modal fade" id="wh-share-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fab fa-facebook-messenger me-2" style="color:#0099ff;"></i>
                    გაგზავნა — <span id="wh-share-current">1</span> / <span id="wh-share-total">1</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <img id="wh-share-preview" src="" style="max-height:180px;max-width:100%;border-radius:8px;object-fit:contain;margin-bottom:16px;">
                <div class="d-flex gap-2 justify-content-center">
                    <a id="wh-share-messenger-btn" href="#" class="btn btn-sm px-4 fw-bold" style="background:#0099ff;color:#fff;" target="_blank">
                        <i class="fab fa-facebook-messenger me-1"></i> Messenger-ში გაგზავნა
                    </a>
                    <button onclick="whShareCopyUrl()" class="btn btn-sm btn-secondary px-3" title="კოპირება">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer justify-content-between py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">დახურვა</button>
                <button id="wh-share-next-btn" type="button" class="btn btn-primary btn-sm px-4">
                    შემდეგი <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Image Zoom Modal --}}
<div class="modal fade" id="modal-img-zoom" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:90vw;width:auto;">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" style="z-index:10;"></button>
                <img id="zoom-img-src" src="" style="max-height:85vh;max-width:85vw;border-radius:8px;object-fit:contain;">
            </div>
        </div>
    </div>
</div>

{{-- Write-Off Modal --}}
<div class="modal fade" id="modal-writeoff" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#e67e22; color:#fff; border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📉 ჩამოწერა</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- პროდუქტი --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">პროდუქტი</label>
                    <select id="wo-product" class="form-select">
                        <option value="">— აირჩიე —</option>
                    </select>
                </div>

                {{-- ზომა --}}
                <div class="mb-3" id="wo-size-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">ზომა</label>
                    <select id="wo-size" class="form-select"></select>
                </div>

                {{-- ნაშთის ინფო --}}
                <div id="wo-stock-info" style="display:none;"
                     class="p-3 rounded mb-3 d-flex gap-4 align-items-center"
                     style="background:#f9f9f9; border:1px solid #ddd;">
                    <div class="text-center">
                        <div class="fw-bold text-success" style="font-size:22px;" id="wo-available">0</div>
                        <div class="text-muted" style="font-size:11px;">✅ ხელმისაწვდომი</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold" style="font-size:22px; color:#555;" id="wo-physical">0</div>
                        <div class="text-muted" style="font-size:11px;">📦 ფიზ. ჯამი</div>
                    </div>
                </div>

                {{-- რაოდენობა --}}
                <div class="mb-3" id="wo-qty-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">რაოდენობა</label>
                    <input type="number" id="wo-qty" class="form-control text-center fw-bold"
                           min="1" value="1" style="font-size:20px;">
                    <small class="text-muted" id="wo-qty-hint"></small>
                </div>

                {{-- შენიშვნა --}}
                <div class="mb-2" id="wo-note-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">შენიშვნა</label>
                    <input type="text" id="wo-note" class="form-control form-control-sm"
                           placeholder="სურვილისამებრ...">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-danger" id="btn-writeoff-save"
                        onclick="submitWriteOff()" style="display:none;">
                    <i class="fa fa-check"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Offcanvas: პროდუქტის ლოგი --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-log"
     style="width:min(680px, 100vw);" data-bs-backdrop="false">
    <div class="offcanvas-header" style="background:#222d32; color:#fff;">
        <h5 class="offcanvas-title fw-bold" id="offcanvas-log-title">📋 საწყობის ისტორია</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="table-responsive h-100">
        <table id="log-table" class="table table-sm table-hover table-bordered mb-0 w-100" style="font-size:13px;">
            <thead>
                <tr>
                    <th>თარიღი</th>
                    <th>ოპერაცია</th>
                    <th>ცვლილება</th>
                    <th>შენიშვნა</th>
                    <th>მომხ.</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
window.whZoom = function(url) {
    document.getElementById('zoom-img-src').src = url;
    new bootstrap.Modal(document.getElementById('modal-img-zoom')).show();
};

// ── MESSENGER / COPY ─────────────────────────────────────────
window.openMessenger = function(url) {
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    if (isMobile) {
        window.location.href = 'fb-messenger://share/?link=' + encodeURIComponent(url);
    } else {
        window.open('https://www.facebook.com/dialog/send?link=' + encodeURIComponent(url) + '&app_id=966242223397117&redirect_uri=' + encodeURIComponent(url), '_blank', 'width=600,height=400');
    }
};
window.copyImageUrl = function(url) {
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({ icon:'success', title:'კოპირებულია', text:'ჩასვი სადაც გინდა', timer:1800, showConfirmButton:false });
    }).catch(function() { prompt('სურათის ლინკი:', url); });
};

// ── MULTI-SELECT ─────────────────────────────────────────────
var whSelected = new Map(); // url → price

function whUpdateBulkBar() {
    var n = whSelected.size;
    document.getElementById('wh-bulk-count').textContent = n;
    document.getElementById('wh-bulk-bar').classList.toggle('show', n > 0);
}
window.whClearSelection = function() {
    whSelected.clear();
    document.querySelectorAll('.wh-cb').forEach(function(cb){ cb.checked = false; });
    document.getElementById('wh-select-all').checked = false;
    whUpdateBulkBar();
};
$(document).on('change', '.wh-cb', function() {
    var url = $(this).data('url');
    if (!url) return;
    if (this.checked) {
        whSelected.set(url, { price: $(this).data('price')||'', size: $(this).data('size')||'' });
    } else { whSelected.delete(url); }
    whUpdateBulkBar();
});
$('#wh-select-all').on('change', function() {
    var checked = this.checked;
    document.querySelectorAll('.wh-cb').forEach(function(cb) {
        var url = cb.dataset.url; if (!url) return;
        cb.checked = checked;
        if (checked) { whSelected.set(url, { price: cb.dataset.price||'', size: cb.dataset.size||'' }); }
        else { whSelected.delete(url); }
    });
    whUpdateBulkBar();
});
$(document).on('draw.dt', '#stock-table', function() {
    document.getElementById('wh-select-all').checked = false;
    whSelected.clear();
    whUpdateBulkBar();
});
window.whBulkCopy = function() {
    if (!whSelected.size) return;
    var parts = [];
    whSelected.forEach(function(meta, url) {
        var parts2 = [];
        if (meta.size)  parts2.push('ზომა ' + meta.size);
        if (meta.price) parts2.push('ფასი ' + meta.price + '₾');
        parts.push(url + (parts2.length ? '\n' + parts2.join(' / ') : ''));
    });
    navigator.clipboard.writeText(parts.join('\n\n')).then(function() {
        Swal.fire({ icon:'success', title: whSelected.size + ' ლინკი კოპირდა', timer:1800, showConfirmButton:false });
    });
};
var whShareQueue = [];
var whShareIndex = 0;

function whShareShowCurrent() {
    var url = whShareQueue[whShareIndex];
    document.getElementById('wh-share-preview').src = url;
    document.getElementById('wh-share-current').textContent = whShareIndex + 1;
    document.getElementById('wh-share-total').textContent   = whShareQueue.length;
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    var messengerUrl = isMobile
        ? 'fb-messenger://share/?link=' + encodeURIComponent(url)
        : 'https://www.facebook.com/dialog/send?link=' + encodeURIComponent(url) + '&app_id=966242223397117&redirect_uri=' + encodeURIComponent(url);
    document.getElementById('wh-share-messenger-btn').href = messengerUrl;
    var nextBtn = document.getElementById('wh-share-next-btn');
    var isLast  = whShareIndex >= whShareQueue.length - 1;
    nextBtn.style.display = isLast ? 'none' : '';
}

window.whShareCopyUrl = function() {
    var url = whShareQueue[whShareIndex] || '';
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({ icon:'success', title:'კოპირებულია', timer:1500, showConfirmButton:false });
    });
};

document.getElementById('wh-share-next-btn').addEventListener('click', function() {
    if (whShareIndex < whShareQueue.length - 1) {
        whShareIndex++;
        whShareShowCurrent();
    }
});

window.whBulkMessenger = function() {
    if (!whSelected.size) return;
    whShareQueue = Array.from(whSelected.keys());
    whShareIndex = 0;
    whShareShowCurrent();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('wh-share-modal')).show();
};

$(function() {
    var logTable        = null;
    var isAdmin         = {{ auth()->user()->role == 'admin' ? 'true' : 'false' }};

    // ─── Financial summary bar (admin only) ──────────────────────────
    if (isAdmin) {
        $.getJSON("{{ route('warehouse.financials') }}", function(d) {
            $('#fin-available').text(d.available + ' ც');
            $('#fin-cost').text('$' + parseFloat(d.cost).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}));
            $('#fin-revenue').text(parseFloat(d.revenue).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₾');
            var profit = parseFloat(d.profit);
            $('#fin-profit').text(profit.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₾')
                            .css('color', profit >= 0 ? 'var(--wh-green)' : 'var(--wh-red)');
        });
    }
    // ─────────────────────────────────────────────────────────────────

    var stockTable = $('#stock-table').DataTable({
        processing: true, serverSide: true,
        responsive: false,
        pageLength: 25,
        order: [[9, 'desc']],
        dom: 't<"d-flex justify-content-between align-items-center mt-2 px-2"ip>',
        ajax: {
            url: "{{ route('warehouse.apiStock') }}",
            data: function(d) { d.category_id = $('#filter-category').val(); d.sizes = $('#filter-size').val() || []; }
        },
        columns: [
            { data: null, orderable:false, searchable:false,
              render: function(data, type) {
                  if (type !== 'display') return '';
                  var url  = data.image_url_raw || '';
                  var cb   = url ? '<span class="wh-sc-cb"><input type="checkbox" class="wh-cb" data-url="'+url+'" data-price="'+(data.price_raw||'')+'" data-size="'+(data.size||'')+'"></span>' : '<span class="wh-sc-cb"></span>';
                  var img  = data.product_image || '';
                  var name = data.product_name  || '';
                  var code = data.product_code  || '';
                  var size = data.size           || '';
                  return '<div class="wh-sc-inner">'+cb+img+'<div class="wh-sc-text"><div class="wh-sc-name">'+name+'</div><div class="wh-sc-code">'+code+'</div></div>'+(size?'<span class="wh-sc-size">'+size+'</span>':'')+'</div>';
              }
            },
            {data:'product_image', orderable:false, searchable:false, visible:false},
            {data:'product_name', visible:false},
            {data:'product_code', visible:false},
            {data:'size',         visible:false},
            {data:'physical_qty', responsivePriority: 5,
             render: v => `<span class="qty-badge ${v>0?'qty-physical':'qty-zero'}">${v}</span>`},
            {data:'incoming_qty', responsivePriority: 8,
             render: v => `<span class="qty-badge ${v>0?'qty-incoming':'qty-zero'}">${v}</span>`},
            {data:'return_incoming_qty', responsivePriority: 9,
             render: v => `<span class="qty-badge ${v>0?'qty-return-incoming':'qty-zero'}">${v}</span>`},
            {data:'reserved_qty', responsivePriority: 10,
             render: v => `<span class="qty-badge ${v>0?'qty-reserved':'qty-zero'}">${v}</span>`},
            {data:'available',    responsivePriority: 3,
             render: v => `<span class="qty-badge ${v>0?'qty-available':'qty-zero'}">${v}</span>`},
            {data:'fifo_cost', orderable:false, responsivePriority: 10, visible: isAdmin},
            {data:'status_badge', orderable:false, responsivePriority: 6},
            {data:'action',       orderable:false, responsivePriority: 4},
            {data:'image_url_raw', visible:false},
            {data:'price_raw',     visible:false},
        ],
        drawCallback: function() {
            var d=this.api().rows().data(), ph=0,inc=0,ret=0,res=0,low=0;
            d.each(function(r){ if(r.is_divisible) return; ph+=parseInt(r.physical_qty)||0; inc+=parseInt(r.incoming_qty)||0; ret+=parseInt(r.return_incoming_qty)||0; res+=parseInt(r.reserved_qty)||0; if(parseInt(r.available)<=3)low++; });
            $('#stat-physical').text(ph); $('#stat-incoming').text(inc); $('#stat-return-incoming').text(ret); $('#stat-reserved').text(res); $('#stat-low').text(low);
        },
        initComplete: function() {
            $('#stock-table_wrapper').children('div.d-flex')
                .detach()
                .insertAfter('.mod-card .table-responsive');
        }
    });

    $('#dt-search').on('keyup', function() { stockTable.search(this.value).draw(); });
    $('#dt-page-length').on('change', function() { stockTable.page.len(this.value).draw(); });
    $('#filter-category').select2({ placeholder: 'ყველა კატეგორია', allowClear: true, width: '170px' });
    $('#filter-category').on('change', function() { stockTable.ajax.reload(); });
    $('#filter-size').select2({ placeholder: 'ყველა ზომა', allowClear: true, width: '180px' });
    $('#filter-size').on('change', function() { stockTable.ajax.reload(); });

    // ══ WRITE-OFF MODAL ══
    var woStockData   = [];
    var woCurrentType = 'writeoff';

    window.openWriteOffModal = function() {
        $('#wo-product').html('<option value="">— აირჩიე —</option>');
        $('#wo-size-wrap, #wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
        $('#btn-writeoff-save').hide();
        $('#wo-qty').val(1);
        $('#wo-note').val('');

        // load available stock
        $.get("{{ route('warehouse.availableStock') }}", function(data) {
            woStockData = data;

            // unique products
            var seen = {};
            data.forEach(function(r) {
                if (!seen[r.product_id]) {
                    seen[r.product_id] = true;
                    $('#wo-product').append(
                        '<option value="' + r.product_id + '">' + r.product_name + '</option>'
                    );
                }
            });
        });

        new bootstrap.Modal(document.getElementById('modal-writeoff')).show();
    };

    $('#wo-product').on('change', function() {
        var pid = $(this).val();
        $('#wo-size').empty();
        $('#wo-size-wrap, #wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
        $('#btn-writeoff-save').hide();

        if (!pid) return;

        var sizes = woStockData.filter(r => r.product_id == pid);
        if (sizes.length === 1) {
            // ერთი ზომა — პირდაპირ გადავდგათ
            $('#wo-size').append('<option value="' + sizes[0].size + '">' + sizes[0].size + '</option>');
            $('#wo-size-wrap').show();
            $('#wo-size').trigger('change');
        } else {
            $('#wo-size').append('<option value="">— ზომა —</option>');
            sizes.forEach(function(r) {
                $('#wo-size').append('<option value="' + r.size + '">' + r.size + '</option>');
            });
            $('#wo-size-wrap').show();
        }
    });

    $('#wo-size').on('change', function() {
        var pid  = $('#wo-product').val();
        var size = $(this).val();
        if (!pid || !size) {
            $('#wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
            $('#btn-writeoff-save').hide();
            return;
        }

        var row = woStockData.find(r => r.product_id == pid && r.size === size);
        if (!row) return;

        $('#wo-available').text(row.available);
        $('#wo-physical').text(row.physical);
        $('#wo-qty').val(1).attr('max', row.available);
        $('#wo-qty-hint').text('მაქს. ხელმისაწვდომი: ' + row.available);
        $('#wo-stock-info, #wo-qty-wrap, #wo-note-wrap').show();
        $('#btn-writeoff-save').show();
    });

    window.submitWriteOff = function() {
        var pid  = $('#wo-product').val();
        var size = $('#wo-size').val();
        var qty  = parseInt($('#wo-qty').val()) || 0;
        var max  = parseInt($('#wo-qty').attr('max')) || 0;

        if (!pid || !size)  { swal('შეცდომა', 'აირჩიეთ პროდუქტი და ზომა', 'error'); return; }
        if (qty < 1)        { swal('შეცდომა', 'რაოდენობა უნდა იყოს მინიმუმ 1', 'error'); return; }
        if (qty > max)      { swal('შეცდომა', 'მაქსიმუმ ' + max + ' ერთ.', 'error'); return; }

        $('#btn-writeoff-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ route('warehouse.writeOff') }}",
            type: 'POST',
            data: {
                product_id: pid,
                size:       size,
                qty:        qty,
                type:       woCurrentType,
                note:       $('#wo-note').val(),
                _token:     "{{ csrf_token() }}"
            },
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-writeoff')).hide();
                stockTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 2000 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-writeoff-save').prop('disabled', false).html('<i class="fa fa-check"></i> დადასტურება');
            }
        });
    };

    // ══ OFFCANVAS LOG ══
    window.openStockLog = function(productId, productName, size) {
        $('#offcanvas-log-title').text('📋 ' + productName + (size ? ' / ' + size : ''));

        // DataTable init ან reload
        if (logTable) {
            logTable.ajax.url(buildLogUrl(productId, size)).load();
        } else {
            logTable = $('#log-table').DataTable({
                processing: true, serverSide: true,
                ajax: buildLogUrl(productId, size),
                order: [[0, 'desc']],
                pageLength: 20,
                columns: [
                    {data:'created_at',   width:'120px'},
                    {data:'action_badge', orderable:false},
                    {data:'qty_badge',    orderable:false},
                    {data:'note',         orderable:false, defaultContent:'—',
                     render: v => v ? `<span title="${v}">${v.length>30?v.substring(0,30)+'…':v}</span>` : '—'},
                    {data:'user_name',    orderable:false, width:'80px'},
                ],
            });
        }

        new bootstrap.Offcanvas(document.getElementById('offcanvas-log')).show();
    };

    function buildLogUrl(productId, size) {
        return "{{ route('warehouse.apiLogs') }}?product_id=" + productId + "&size=" + encodeURIComponent(size);
    }
});

/* ══ STICKY COLUMN SCROLL COLLAPSE ══ */
(function() {
    var wrap = document.querySelector('.mod-card .table-responsive');
    if (wrap) {
        wrap.addEventListener('scroll', function() {
            this.classList.toggle('wh-tbl-scrolled', this.scrollLeft > 5);
        });
    }
})();

/* ══ MOBILE FILTER DRAWER ══ */
window.toggleWhDrawer = function() {
    var panel = document.getElementById('wh-filter-drawer');
    var btn   = document.getElementById('wh-filter-btn');
    var open  = panel.classList.contains('open');
    panel.classList.toggle('open', !open);
    btn.classList.toggle('active', !open);
};
$(document).on('click', function(e) {
    if (!$(e.target).closest('.wh-mob-search-wrap').length) {
        $('#wh-filter-drawer').removeClass('open');
        $('#wh-filter-btn').removeClass('active');
    }
});

/* Mobile search → DataTable */
(function() {
    var _t;
    $('#mob-wh-search').on('input', function() {
        clearTimeout(_t); var v = $(this).val();
        _t = setTimeout(function() { $('#dt-search').val(v).trigger('keyup'); }, 300);
    });
})();

/* Mobile category ↔ desktop */
$('#mob-filter-category').on('change', function() {
    $('#filter-category').val($(this).val()).trigger('change');
    updateWhFilterBadge();
});
$('#filter-category').on('change', function() {
    $('#mob-filter-category').val($(this).val());
    updateWhFilterBadge();
});

/* Mobile size → desktop */
$('#mob-filter-size').on('change', function() {
    var val = $(this).val() ? [$(this).val()] : [];
    $('#filter-size').val(val).trigger('change');
    updateWhFilterBadge();
});

/* Mobile page length → desktop */
$('#mob-dt-page-length').on('change', function() {
    $('#dt-page-length').val($(this).val()).trigger('change');
});

/* Filter badge counter */
function updateWhFilterBadge() {
    var count = 0;
    if ($('#filter-category').val()) count++;
    var sz = $('#filter-size').val();
    if (sz && sz.length > 0) count++;
    $('#wh-filter-badge').text(count || '');
    $('#wh-filter-btn').toggleClass('has-active', count > 0);
}
</script>
@endsection