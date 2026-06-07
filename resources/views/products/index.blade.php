@extends('layouts.master')
@section('page_title')<i class="fa fa-cubes me-2" style="color:#3498db;"></i>პროდუქტები@endsection


@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
/* ── Design tokens ── */
:root {
    --prd-surface:    #ffffff;
    --prd-surface2:   #f6f7fb;
    --prd-border:     rgba(99,115,150,.20);
    --prd-text-1:     #1a2235;
    --prd-text-2:     #3d4a5c;
    --prd-text-3:     #7a8a9e;
    --prd-blue:       #2563eb;
    --prd-blue-dim:   #eff6ff;
    --prd-r-md:       12px;
    --prd-r-sm:       8px;
    --prd-sh-sm:      0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03);
}

/* ── Select2 ── */
.select2-container--default .select2-selection--single { height: 38px; border: 1px solid #dee2e6; border-radius: 6px; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; padding-left: 10px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
/* ── Toggle ── */
.form-switch .form-check-input { width: 2.5em; height: 1.3em; cursor: pointer; }
.switch { position: relative; display: inline-block; width: 46px; height: 24px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
.switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }
/* ── Image thumb ── */
.img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; cursor: zoom-in; transition: transform 0.15s; border: 1px solid #dee2e6; }
.img-thumb:hover { transform: scale(1.1); }
/* ── Desktop table ── */
#products-table td { vertical-align: middle; }
#products-table td:nth-child(6) { padding: 4px 3px; cursor: zoom-in; }

/* Mobile-only elements: hidden on desktop */
.prd-mob-action-bar  { display: none; }
.prd-mob-search-wrap { display: none; }
.prd-mob-cell        { display: none !important; }
.prd-sizes-toggle    { display: none; }

/* ══════════════════════════════════════════════
   MOBILE CARD VIEW  ≤767px
══════════════════════════════════════════════ */
@media (max-width: 767px) {

    /* Hide desktop header + toolbar */
    .mod-header  { display: none !important; }
    .mod-toolbar { display: none !important; }

    /* Page padding */
    .mod-wrap { padding: 8px 12px 70px !important; }

    /* Strip card from mod-card */
    .mod-card {
        background: transparent !important;
        border: none !important; box-shadow: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }
    .table-responsive { overflow: visible !important; }

    /* ── Mobile action bar ── */
    .prd-mob-action-bar {
        display: flex; flex-wrap: nowrap; align-items: center; gap: 6px;
        padding: 0 0 8px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .prd-mob-action-bar::-webkit-scrollbar { display: none; }

    /* ── Mobile search bar (below action bar) ── */
    .prd-mob-search-wrap { display: block; margin-bottom: 10px; position: relative; }
    .prd-mob-search {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid var(--prd-border);
        border-radius: 14px; padding: 10px 6px 10px 14px;
        box-shadow: var(--prd-sh-sm);
    }
    .prd-mob-search > i { font-size: 13px; color: var(--prd-text-3); flex-shrink: 0; }
    .prd-mob-search input {
        flex: 1; min-width: 0; background: none; border: none; outline: none;
        font-size: 14px; color: var(--prd-text-1); font-family: inherit;
    }
    .prd-mob-search input::placeholder { color: var(--prd-text-3); font-size: 13px; }

    /* ── Inline filter panel (drops below search bar) ── */
    @keyframes prdPanelIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .prd-filter-panel {
        display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid var(--prd-border);
        border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.12); z-index: 200;
        overflow: hidden;
    }
    .prd-filter-panel.open { display: block; animation: prdPanelIn .18s ease; }
    .prd-filter-panel-body {
        display: flex; flex-direction: column; padding: 12px 16px 14px; gap: 10px;
    }
    .prd-filter-panel .prd-fp-label {
        font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 3px;
    }
    .prd-filter-panel select {
        width: 100%; height: 40px; font-size: 13.5px;
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0 10px;
        background: #fff; color: #1e293b; font-family: inherit;
    }
    .prd-filter-panel .prd-fp-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 6px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #475569;
    }
    .prd-mab-btn {
        display: inline-flex; align-items: center; gap: 5px;
        white-space: nowrap; cursor: pointer; font-family: inherit;
        font-size: 12.5px; font-weight: 600; line-height: 1;
        padding: 7px 13px; border-radius: 22px; border: 1.5px solid transparent;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
        transition: background .15s, transform .1s; flex-shrink: 0; text-decoration: none;
    }
    .prd-mab-btn:active { transform: scale(.93); }
    .prd-mab-btn > i { font-size: 10px; }
    .prd-mab-primary { background: var(--prd-blue); border-color: var(--prd-blue); color: #fff; box-shadow: 0 2px 10px rgba(37,99,235,.28); }
    .prd-mab-ghost   { background: var(--prd-surface); border-color: var(--prd-border); color: var(--prd-text-2); }

    /* ── Table → block ── */
    #products-table       { display: block !important; width: 100% !important; }
    #products-table thead { display: none !important; }
    #products-table tbody { display: block !important; }
    #products-table tfoot { display: none !important; }

    /* ── Row = card ── */
    #products-table tbody tr {
        display: block !important;
        background: var(--prd-surface) !important;
        border-radius: var(--prd-r-md) !important;
        margin: 0 0 10px !important;
        box-shadow: var(--prd-sh-sm) !important;
        border: 1px solid var(--prd-border) !important;
        overflow: hidden;
    }

    /* Hide all original cells — card cell only */
    #products-table tbody tr td:not(.prd-mob-cell) { display: none !important; }

    /* ── Card cell: full width, no padding (card handles it) ── */
    .prd-mob-cell {
        display: block !important;
        padding: 0 !important; border: none !important;
    }

    /* ── Card structure ── */
    .prd-mob-card { display: flex; flex-direction: column; }

    /* Header: photo | info | actions */
    .prd-mob-hdr {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 10px 10px 12px;
    }
    .prd-mob-photo {
        flex-shrink: 0; width: 56px; height: 56px;
        border-radius: var(--prd-r-sm); overflow: hidden;
        border: 1px solid var(--prd-border);
        display: flex; align-items: center; justify-content: center;
    }
    .prd-mob-photo .img-thumb {
        width: 56px !important; height: 56px !important;
        border-radius: 0 !important; border: none !important;
        margin: 0 !important; display: block;
    }
    .prd-mob-info { flex: 1; min-width: 0; }
    .prd-mob-name {
        font-size: 13.5px; font-weight: 700; color: var(--prd-text-1);
        line-height: 1.35; word-break: break-word;
    }
    .prd-mob-meta {
        display: flex; flex-wrap: wrap; gap: 4px; align-items: center;
        margin-top: 4px;
    }
    .prd-meta-item {
        font-size: 11px; color: var(--prd-text-2); white-space: nowrap;
        background: var(--prd-surface2); border: 1px solid var(--prd-border);
        border-radius: 5px; padding: 1px 6px; line-height: 1.6;
    }
    .prd-meta-code { font-family: monospace; color: var(--prd-text-2); font-weight: 600; }
    .prd-mob-act {
        flex-shrink: 0; display: flex; gap: 4px; align-items: flex-start;
        margin-left: auto;
    }
    .prd-mob-act .btn-xs {
        padding: 5px 8px !important; font-size: 11px !important;
        border-radius: 7px !important; touch-action: manipulation;
    }

    /* Body: price + status badges — inside header info, below name/meta */
    .prd-mob-bdy {
        display: flex; align-items: center; gap: 5px; flex-wrap: wrap;
        padding: 5px 0 0;
    }
    .prd-mob-price {
        font-size: 14px; font-weight: 800; color: var(--prd-text-1);
    }
    .prd-mob-badges { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
    .prd-mob-badges .btn { font-size: 11px !important; padding: 3px 8px !important; border-radius: 6px !important; touch-action: manipulation; }
    .prd-mob-badges .badge { font-size: 11px; }

    /* Sizes + bundle: separated by border-top */
    .prd-mob-sizes {
        padding: 7px 12px 8px;
        border-top: 1px solid var(--prd-border);
        display: flex; flex-direction: column; gap: 4px;
    }
    .prd-mob-sizes-row {
        display: flex; align-items: flex-start; gap: 6px;
    }
    .prd-mob-bundle-tag {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11px; font-weight: 600;
        color: var(--prd-blue); background: var(--prd-blue-dim);
        border-radius: 5px; padding: 2px 7px;
        flex-shrink: 0; margin-left: auto;
    }
    .prd-mob-sizes-inner {
        overflow: hidden; max-height: 30px;
        transition: max-height 0.25s ease; line-height: 1.9;
    }
    .prd-mob-sizes-inner.expanded { max-height: 400px; }
    .prd-sizes-toggle {
        display: inline-flex; align-items: center; gap: 3px;
        margin-top: 4px; padding: 2px 0;
        font-size: 11.5px; font-weight: 700;
        color: var(--prd-blue); background: none; border: none;
        cursor: pointer; font-family: inherit;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
    }
}
</style>
@endsection

@section('content')

<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-cubes me-2" style="color:#3498db;"></i>პროდუქტები</h2>
            <p class="mod-subtitle">პროდუქტების კატალოგის მართვა</p>
        </div>
        <div class="mod-actions">
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'products', 'can_create'))
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი</span>
            </button>
            @endif
        </div>
    </div>

    {{-- ── MOBILE ACTION BAR ── --}}
    <div class="prd-mob-action-bar">
        @if(\App\Models\RolePermission::check(auth()->user()->role, 'products', 'can_create'))
        <button onclick="addForm()" class="prd-mab-btn prd-mab-primary">
            <i class="fa fa-plus"></i> ახალი
        </button>
        @endif
    </div>

    {{-- ── MOBILE SEARCH BAR + inline filter panel ── --}}
    <div class="prd-mob-search-wrap">
        <div class="prd-mob-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" id="mob-prd-search" placeholder="პროდუქტის ძებნა..." autocomplete="off">
            <button type="button" class="mob-ts-filter-btn" id="prd-filter-btn" onclick="togglePrdDrawer()">
                <i class="fa fa-sliders"></i>
                <span class="mob-ts-filter-badge" id="prd-filter-badge"></span>
            </button>
        </div>
        {{-- Filter panel drops below search bar --}}
        <div class="prd-filter-panel" id="prd-filter-drawer">
            <div class="prd-filter-panel-body">
                <div>
                    <div class="prd-fp-label">საწყობი</div>
                    <select id="mob-filter-warehouse">
                        <option value="">ყველა</option>
                        <option value="1">მხოლოდ საწყობიდან</option>
                        <option value="0">ჩვეულებრივი</option>
                    </select>
                </div>
                @if(Auth::user()->role === 'admin')
                <div class="prd-fp-row">
                    <span>წაშლილი პროდუქტები</span>
                    <label class="switch mb-0">
                        <input type="checkbox" id="mob-toggle-deleted">
                        <span class="switch-slider"></span>
                    </label>
                </div>
                @endif
                <div>
                    <div class="prd-fp-label">ჩანაწ. რაოდ.</div>
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
            <select id="filter-warehouse-only" class="form-select form-select-sm" style="width:auto;">
                <option value="">ყველა</option>
                <option value="1">მხოლოდ საწყობიდან</option>
                <option value="0">ჩვეულებრივი</option>
            </select>
            <div class="mod-spacer"></div>
            @if(Auth::user()->role === 'admin')
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:12px;color:#94a3b8;font-weight:500;">Deleted</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="toggle-deleted" role="switch">
                </div>
            </div>
            @endif
        </div>
        <div class="table-responsive">
            <table id="products-table" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>სახელი</th>
                        <th>კოდი</th>
                        <th>კატეგორია</th>
                        <th>ბრენდი</th>
                        <th>კომპლექტი</th>
                        <th class="text-end">ფასი</th>
                        <th class="text-center" style="width:56px;">Img</th>
                        <th>ზომები</th>
                        <th class="text-center" style="width:100px;">საწყობი</th>
                        <th class="text-center" style="width:62px;">სტატუსი</th>
                        <th class="text-center" style="width:70px;">მოქმედება</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap --}}

@include('products.form')

{{-- LIGHTBOX --}}
<div class="modal fade" id="img-lightbox" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:90vw; width:auto;">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center" onclick="bootstrap.Modal.getInstance(document.getElementById('img-lightbox')).hide()">
                <img id="img-lightbox-img" src="" style="max-width:90vw; max-height:90vh; border-radius:8px; cursor:zoom-out;">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="status-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">სტატუსის შეცვლა</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <input type="hidden" id="status-product-id">
                <select id="status-select" class="form-select form-select-sm">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-sm btn-success" onclick="saveStatus()">შენახვა</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script>
var save_method;
var table;

$(function() {
    table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [],
        ajax: {
            url: "{{ route('api.products') }}",
            data: function(d) {
                d.warehouse_only = $('#filter-warehouse-only').val();
            }
        },
        columns: [
            { data: 'name',                                                                              },
            { data: 'product_code',              width: '90px'                                           },
            { data: 'category_name',  orderable: false, searchable: false, width: '110px'               },
            { data: 'brand_name',     orderable: false, searchable: false, width: '100px'               },
            { data: 'bundle_name',    orderable: false, searchable: false, width: '110px'               },
            { data: 'price_geo',      className: 'text-end',               width: '70px'                },
            { data: 'show_photo',     orderable: false, searchable: false, className: 'text-center', width: '56px'  },
            { data: 'format_sizes',   orderable: false, searchable: false, width: '90px'               },
            { data: 'warehouse_only_badge', orderable: false, searchable: false, className: 'text-center', width: '100px' },
            { data: 'status_stock',   orderable: false, searchable: false, className: 'text-center', width: '62px'  },
            { data: 'action',         orderable: false, searchable: false, className: 'text-center', width: '70px'  }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            info:       '_START_–_END_ of _TOTAL_',
            paginate:   { previous: '‹', next: '›' }
        },
        dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
        pageLength: 25,
        drawCallback: function() {
            buildMobilePrdCards();
        }
    });

    /* ── Desktop controls ── */
    $('#toggle-deleted').on('change', function() {
        var deleted = $(this).is(':checked');
        if (deleted) {
            table.ajax.url("{{ route('api.deleted-products') }}").load();
        } else {
            table.ajax.url("{{ route('api.products') }}").load();
        }
        updatePrdFilterBadge();
    });

    $('#filter-warehouse-only').on('change', function() {
        table.ajax.reload();
        updatePrdFilterBadge();
    });

    $('#dt-page-length').on('change', function() {
        table.page.len(parseInt($(this).val())).draw();
    });

    (function() {
        var _t;
        $('#dt-search').on('input', function() {
            clearTimeout(_t); var v = $(this).val();
            _t = setTimeout(function() { table.search(v).draw(); }, 300);
        });
    })();
});

/* ── Mobile topbar search ── */
(function() {
    var _t;
    $('#mob-prd-search').on('input', function() {
        clearTimeout(_t); var v = $(this).val();
        _t = setTimeout(function() { table.search(v).draw(); }, 300);
        $('#dt-search').val(v);
    });
})();

/* ── Mobile filter panel (inline below search) ── */
function togglePrdDrawer() {
    var panel  = $('#prd-filter-drawer');
    var isOpen = panel.hasClass('open');
    panel.toggleClass('open', !isOpen);
    $('#prd-filter-btn').toggleClass('active', !isOpen);
}

/* Close panel on outside click */
$(document).on('click', function(e) {
    if (!$(e.target).closest('.prd-mob-search-wrap').length) {
        $('#prd-filter-drawer').removeClass('open');
        $('#prd-filter-btn').removeClass('active');
    }
});

function updatePrdFilterBadge() {
    var count = 0;
    if ($('#filter-warehouse-only').val()) count++;
    if ($('#toggle-deleted').is(':checked')) count++;
    $('#prd-filter-badge').text(count || '');
    $('#prd-filter-btn').toggleClass('has-active', count > 0);
}

/* Mobile warehouse filter → sync with desktop */
$('#mob-filter-warehouse').on('change', function() {
    $('#filter-warehouse-only').val($(this).val()).trigger('change');
});
/* Desktop → mobile sync */
$('#filter-warehouse-only').on('change', function() {
    $('#mob-filter-warehouse').val($(this).val());
});

/* Mobile deleted toggle → sync with desktop */
$('#mob-toggle-deleted').on('change', function() {
    $('#toggle-deleted').prop('checked', $(this).is(':checked')).trigger('change');
});
$('#toggle-deleted').on('change', function() {
    $('#mob-toggle-deleted').prop('checked', $(this).is(':checked'));
});

/* Mobile page length */
$('#mob-dt-page-length').on('change', function() {
    table.page.len(parseInt($(this).val())).draw();
    $('#dt-page-length').val($(this).val());
});

/* ── Build mobile cards ── */
function buildMobilePrdCards() {
    if (window.innerWidth > 767) return;
    $('#products-table tbody tr').each(function() {
        var tr  = $(this);
        var tds = tr.find('td');

        var name   = tds.eq(0).html()  || '';
        var code   = $.trim(tds.eq(1).text());
        var cat    = $.trim(tds.eq(2).text());
        var brand  = $.trim(tds.eq(3).text());
        var bundle = $.trim(tds.eq(4).text());
        var price  = $.trim(tds.eq(5).text());
        var photo  = tds.eq(6).html()  || '';
        var sizes  = tds.eq(7).html()  || '';
        var wareh  = tds.eq(8).html()  || '';
        var status = tds.eq(9).html()  || '';
        var action = tds.eq(10).html() || '';

        /* Filter out empty / dash-only values */
        function notEmpty(v) { return v && v !== '-' && v !== '—' && v !== '–'; }

        /* Meta chips: code · category · brand (skip dashes) */
        var meta = '';
        if (notEmpty(code))  meta += '<span class="prd-meta-item prd-meta-code">' + $('<div>').text(code).html() + '</span>';
        if (notEmpty(cat))   meta += '<span class="prd-meta-item">' + $('<div>').text(cat).html() + '</span>';
        if (notEmpty(brand)) meta += '<span class="prd-meta-item">' + $('<div>').text(brand).html() + '</span>';

        /* Warehouse badge — hide when empty or dash */
        var warehText  = wareh.replace(/<[^>]+>/g, '').trim();
        var warehBadge = notEmpty(warehText) ? wareh : '';

        /* Sizes + bundle section */
        var sizesText  = sizes.replace(/<[^>]+>/g, '').trim();
        var hasBundle  = notEmpty(bundle);
        var hasSizes   = sizesText.length > 0;
        var bundleTag  = hasBundle
            ? '<span class="prd-mob-bundle-tag"><i class="fa fa-boxes-stacking" style="font-size:9px;opacity:.7;"></i> ' + $('<div>').text(bundle).html() + '</span>'
            : '';
        var sizesSection = (hasSizes || hasBundle)
            ? '<div class="prd-mob-sizes">'
            +   '<div class="prd-mob-sizes-row">'
            +     (hasSizes ? '<div class="prd-mob-sizes-inner">' + sizes + '</div>' : '')
            +     bundleTag
            +   '</div>'
            +   (hasSizes ? '<button type="button" class="prd-sizes-toggle" style="display:none;">ყველა ▾</button>' : '')
            + '</div>'
            : '';

        /* Photo wrapper — only if img exists */
        var photoSection = photo.indexOf('<img') !== -1
            ? '<div class="prd-mob-photo">' + photo + '</div>'
            : '';

        var card = '<div class="prd-mob-card">'
            + '<div class="prd-mob-hdr">'
            +   photoSection
            +   '<div class="prd-mob-info">'
            +     '<div class="prd-mob-name">' + name + '</div>'
            +     (meta ? '<div class="prd-mob-meta">' + meta + '</div>' : '')
            +     '<div class="prd-mob-bdy">'
            +       (price ? '<span class="prd-mob-price">' + $('<div>').text(price).html() + '</span>' : '')
            +       '<div class="prd-mob-badges">' + status + warehBadge + '</div>'
            +     '</div>'
            +   '</div>'
            +   '<div class="prd-mob-act">' + action + '</div>'
            + '</div>'
            + sizesSection
            + '</div>';

        tr.append('<td class="prd-mob-cell" colspan="11">' + card + '</td>');
    });

    /* Show expand toggle for overflowing sizes */
    $('.prd-mob-sizes').each(function() {
        var inner  = $(this).find('.prd-mob-sizes-inner')[0];
        var toggle = $(this).find('.prd-sizes-toggle');
        if (inner && inner.scrollHeight > inner.clientHeight + 2) {
            toggle.show();
        }
    });
}

/* Sizes expand/collapse */
$(document).on('click', '.prd-sizes-toggle', function() {
    var inner    = $(this).prev('.prd-mob-sizes-inner');
    var expanded = inner.hasClass('expanded');
    inner.toggleClass('expanded', !expanded);
    $(this).text(expanded ? 'ყველა ▾' : 'ნაკლები ▴');
});

/* Lightbox */
$(document).on('click', '.img-thumb', function() {
    document.getElementById('img-lightbox-img').src = $(this).data('src') || this.src;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('img-lightbox')).show();
});

/* ── SELECT2 for brand_id ── */
function brandOptionTemplate(option) {
    if (!option.id) return option.text;
    var logo = $(option.element).data('logo');
    if (!logo) return $('<span>' + option.text + '</span>');
    return $('<span><img src="' + logo + '" style="height:20px;width:20px;object-fit:contain;margin-right:6px;border-radius:3px;vertical-align:middle;">' + option.text + '</span>');
}

$(function() {
    $('#brand_id').select2({
        dropdownParent: $('#modal-form'),
        placeholder: '-- Brand --',
        allowClear: true,
        width: '100%',
        templateResult: brandOptionTemplate,
        templateSelection: brandOptionTemplate
    });
});

/* ── ADD ── */
function addForm() {
    save_method = 'add';
    $('input[name=_method]').val('POST');
    $('#form-item')[0].reset();
    $('#id').val('');
    $('#brand_id').val(null).trigger('change');
    $('#bundle_id').val('');
    $('#image-preview').html('<span class="text-muted">No Preview</span>');
    $('#size-checkboxes').html('<span class="text-muted" id="sizes-placeholder" style="font-size:12px;">Choose a category first</span>');
    $('.modal-title').text('Add Product');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
}

/* ── EDIT ── */
function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#form-item')[0].reset();
    $.ajax({
        url: "{{ url('products') }}/" + id + "/edit",
        type: "GET", dataType: "JSON", cache: false,
        success: function(data) {
            $('.modal-title').text('Edit Product');
            $('#id').val(data.id);
            $('#name').val(data.name);
            $('#product_code').val(data.product_code);
            $('#price_geo').val(data.price_geo || data.Price_geo);
            $('#product_status').prop('checked', data.product_status == 1);
            $('#warehouse_only').prop('checked', data.warehouse_only == 1);
            $('#in_warehouse').prop('checked', data.in_warehouse == 1);
            $('#category_id').val(data.category_id);
            updatePriceHint();
            $('#brand_id').val(data.brand_id || null).trigger('change');
            $('#bundle_id').val(data.bundle_id || '');
            var currentSizes = data.sizes ? data.sizes.split(',').map(function(s) { return s.trim(); }) : [];
            filterSizes(currentSizes);
            if (data.image_url) {
                $('#image-preview').html('<img src="' + data.image_url + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">');
            } else {
                $('#image-preview').html('<span class="text-muted">No Preview</span>');
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
        },
        error: function() { swal({ title: 'Error', text: 'Could not fetch data', icon: 'error' }); }
    });
}

function updatePriceHint() {
    var isDivisible = $('#category_id option:selected').data('divisible') == '1';
    $('#price_geo_hint').toggle(isDivisible);
    $('#price_geo_label').text(isDivisible ? 'Price (1ml-ზე)' : 'Price');
    $('#category_id').css('border-color', isDivisible ? '#ef4444' : '').css('color', isDivisible ? '#dc2626' : '').css('font-weight', isDivisible ? '700' : '');
}

/* ── SIZES ── */
function filterSizes(selectedSizes) {
    updatePriceHint();
    selectedSizes = selectedSizes || [];
    var catId       = $('#category_id').val();
    var isDivisible = $('#category_id option:selected').data('divisible') == '1';
    if (isDivisible) { $('#sizes-wrapper').hide(); $('#divisible-notice').show(); return; }
    $('#sizes-wrapper').show(); $('#divisible-notice').hide();
    if (!catId) {
        $('#size-checkboxes').html('<span class="text-muted" style="font-size:12px;">Choose a category first</span>');
        return;
    }
    $.ajax({
        url: "{{ url('get-sizes') }}/" + catId,
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function(data) {
            if (!data || !data.length) {
                $('#size-checkboxes').html('<span class="text-muted" style="font-size:12px;">No sizes</span>');
                return;
            }
            var html = '';
            data.forEach(function(size) {
                var label   = typeof size === 'object' ? size.name : size;
                var checked = selectedSizes.includes(label) ? 'checked' : '';
                html += '<div class="form-check form-check-inline mb-1">'
                      + '<input class="form-check-input" type="checkbox" name="product_sizes[]" value="' + label + '" id="sz_' + label + '" ' + checked + '>'
                      + '<label class="form-check-label small" for="sz_' + label + '">' + label + '</label></div>';
            });
            $('#size-checkboxes').html(html);
        }
    });
}

/* ── SAVE ── */
$(function() {
    $('#form-item').on('submit', function(e) {
        e.preventDefault();
        var id  = $('#id').val();
        var url = (save_method == 'add') ? "{{ url('products') }}" : "{{ url('products') }}/" + id;
        var btn = $(this).find('[type=submit]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: url, type: 'POST',
            data: new FormData(this),
            contentType: false, processData: false,
            success: function(data) {
                bootstrap.Modal.getInstance(document.getElementById('modal-form')).hide();
                table.ajax.reload();
                swal({ title: 'Success!', text: data.message || 'Saved', icon: 'success', timer: 1500 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error';
                swal({ title: 'Error', text: msg, icon: 'error' });
            },
            complete: function() { btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i>Save Changes'); }
        });
    });
});

/* ── DELETE ── */
function deleteData(id) {
    swal({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545' })
    .then(function(result) {
        if (result.value) {
            $.ajax({
                url: "{{ url('products') }}/" + id, type: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function(data) {
                    table.ajax.reload(null, false);
                    if (data.cant_delete) {
                        swal({ title: 'Inactive!', text: data.message, icon: 'warning', timer: 2500 });
                    } else {
                        swal({ title: 'Deleted!', icon: 'success', timer: 1500 });
                    }
                },
                error: function(xhr) { swal({ title: 'Error', text: xhr.responseJSON ? xhr.responseJSON.message : 'Error', icon: 'error' }); }
            });
        }
    });
}

/* ── RESTORE ── */
function restoreData(id) {
    swal({ title: 'Restore?', icon: 'info', showCancelButton: true, confirmButtonText: 'Restore' })
    .then(function(result) {
        if (result.value) {
            $.post("{{ url('products') }}/" + id + "/restore", { _token: '{{ csrf_token() }}' }, function() {
                table.ajax.reload();
                swal({ title: 'Restored!', icon: 'success', timer: 1500 });
            });
        }
    });
}

/* ── STATUS MODAL ── */
function openStatusModal(id, currentStatus) {
    $('#status-product-id').val(id);
    $('#status-select').val(currentStatus);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('status-modal')).show();
}

function saveStatus() {
    var id     = $('#status-product-id').val();
    var status = $('#status-select').val();
    $.ajax({
        url: "{{ url('products') }}/" + id + "/status",
        type: 'POST',
        data: { _method: 'PATCH', _token: '{{ csrf_token() }}', product_status: status },
        success: function() {
            bootstrap.Modal.getInstance(document.getElementById('status-modal')).hide();
            table.ajax.reload(null, false);
        },
        error: function() { swal({ title: 'Error', icon: 'error' }); }
    });
}
</script>
@endsection
