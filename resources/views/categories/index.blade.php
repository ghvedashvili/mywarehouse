@extends('layouts.master')
@section('page_title')<i class="fa fa-tags me-2" style="color:#f39c12;"></i>კატეგორიები@endsection

@section('topbar_search')
<div class="mob-topbar-search">
    <div class="mob-ts-bar">
        <i class="fa fa-magnifying-glass"></i>
        <input type="search" id="mob-cat-search" placeholder="კატეგორიის ძებნა..." autocomplete="off">
        @if(Auth::user()->role === 'admin')
        <button type="button" class="mob-ts-filter-btn" id="cat-filter-btn" onclick="toggleCatDrawer()">
            <i class="fa fa-sliders"></i>
            <span class="mob-ts-filter-badge" id="cat-filter-badge"></span>
        </button>
        @endif
    </div>
</div>
@endsection

@section('top')
<style>
/* ── Design tokens ── */
:root {
    --cat-surface:    #ffffff;
    --cat-surface2:   #f6f7fb;
    --cat-border:     rgba(99,115,150,.20);
    --cat-text-1:     #1a2235;
    --cat-text-2:     #3d4a5c;
    --cat-text-3:     #7a8a9e;
    --cat-blue:       #2563eb;
    --cat-r-md:       12px;
    --cat-sh-sm:      0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03);
}

/* ── Toggle switch ── */
.switch { position: relative; display: inline-block; width: 46px; height: 24px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
.switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }

/* ── Desktop table ── */
#categories-table { table-layout: fixed; width: 100% !important; word-wrap: break-word; }
#categories-table td { white-space: normal !important; word-break: break-word; vertical-align: middle; }
#categories-table td:last-child { white-space: nowrap !important; }
#categories-table .label { display: inline-block; margin-bottom: 2px; }
.table-responsive { overflow-x: hidden; }

/* Mobile action bar & sizes toggle: hidden on desktop */
.cat-mob-action-bar { display: none; }
.cat-sizes-toggle   { display: none; }

/* ══════════════════════════════════════════════
   MOBILE CARD VIEW  ≤767px
══════════════════════════════════════════════ */
@media (max-width: 767px) {

    /* Hide desktop header + toolbar */
    .mod-header  { display: none !important; }
    .mod-toolbar { display: none !important; }

    /* Page padding: leave room for bottom action bar */
    .mod-wrap { padding: 8px 12px 70px !important; }

    /* Strip card styling from mod-card */
    .mod-card {
        background: transparent !important;
        border: none !important; box-shadow: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }
    .table-responsive { overflow: visible !important; }

    /* ── Mobile action bar ── */
    .cat-mob-action-bar {
        display: flex; flex-wrap: nowrap; align-items: center; gap: 6px;
        padding: 0 0 10px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .cat-mob-action-bar::-webkit-scrollbar { display: none; }
    .cat-mab-btn {
        display: inline-flex; align-items: center; gap: 5px;
        white-space: nowrap; cursor: pointer; font-family: inherit;
        font-size: 12.5px; font-weight: 600; line-height: 1;
        padding: 7px 13px; border-radius: 22px; border: 1.5px solid transparent;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
        transition: background .15s, transform .1s; flex-shrink: 0; text-decoration: none;
    }
    .cat-mab-btn:active { transform: scale(.93); }
    .cat-mab-btn > i { font-size: 10px; }
    .cat-mab-primary { background: var(--cat-blue); border-color: var(--cat-blue); color: #fff; box-shadow: 0 2px 10px rgba(37,99,235,.28); }
    .cat-mab-ghost   { background: var(--cat-surface); border-color: var(--cat-border); color: var(--cat-text-2); }
    .cat-mab-sep     { width: 1px; height: 20px; background: var(--cat-border); flex-shrink: 0; }

    /* ── Table → block ── */
    #categories-table       { display: block !important; width: 100% !important; }
    #categories-table thead { display: none !important; }
    #categories-table tbody { display: block !important; }
    #categories-table tfoot { display: none !important; }

    /* ── Row = card (2-column grid: content | action) ── */
    #categories-table tbody tr {
        display: grid !important;
        grid-template-columns: 1fr auto;
        background: var(--cat-surface) !important;
        border-radius: var(--cat-r-md) !important;
        margin: 0 0 10px !important;
        box-shadow: var(--cat-sh-sm) !important;
        border: 1px solid var(--cat-border) !important;
        overflow: hidden;
    }

    /* All cells: full width by default */
    #categories-table tbody td {
        display: block !important;
        grid-column: 1 / -1;
        padding: 8px 12px !important;
        border: none !important;
        font-size: 13px;
    }

    /* ── 1. NAME: card header, col 1 ── */
    #categories-table tbody td:nth-child(1) {
        display: flex !important;
        align-items: center; gap: 6px; flex-wrap: wrap;
        grid-column: 1; grid-row: 1;
        background: var(--cat-surface2) !important;
        border-bottom: 1px solid var(--cat-border) !important;
        padding: 10px 12px !important;
        font-size: 14px !important; font-weight: 600 !important;
        color: var(--cat-text-1);
    }

    /* ── 2. SIZES: below header, full width ── */
    #categories-table tbody td:nth-child(2) {
        display: block !important;
        grid-column: 1 / -1; grid-row: 2;
        padding: 8px 12px 8px !important;
        min-height: 36px;
    }

    /* ── 3. STATUS: always hidden on mobile ── */
    #categories-table tbody td:nth-child(3) { display: none !important; }

    /* ── 4. ACTION: card header, col 2 ── */
    #categories-table tbody td:last-child {
        display: flex !important;
        align-items: center; justify-content: flex-end; gap: 4px;
        grid-column: 2; grid-row: 1;
        background: var(--cat-surface2) !important;
        border-bottom: 1px solid var(--cat-border) !important;
        padding: 8px 10px !important;
    }
    /* Make action buttons touch-friendly */
    #categories-table tbody td:last-child .btn-xs {
        padding: 5px 9px !important; font-size: 12px !important;
        border-radius: 8px !important; touch-action: manipulation;
    }

    /* ── Sizes expand/collapse ── */
    .cat-sizes-inner {
        overflow: hidden;
        max-height: 30px;   /* clips to one label row */
        transition: max-height 0.25s ease;
        line-height: 1.9;
    }
    #categories-table tbody td.cat-expanded .cat-sizes-inner {
        max-height: 400px;
    }
    .cat-sizes-toggle {
        display: inline-flex; align-items: center; gap: 3px;
        margin-top: 4px; padding: 2px 0;
        font-size: 11.5px; font-weight: 700;
        color: var(--cat-blue); background: none; border: none;
        cursor: pointer; font-family: inherit;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
    }
}
</style>
@endsection

@section('content')

{{-- Mobile filter drawer (admin only) --}}
@if(Auth::user()->role === 'admin')
<div class="mob-drawer" id="cat-filter-drawer">
    <div class="mob-drawer-header">
        <span class="mob-drawer-title"><i class="fa fa-sliders" style="font-size:12px;margin-right:7px;opacity:.6;"></i>ფილტრები</span>
        <button type="button" class="mob-drawer-close" onclick="toggleCatDrawer()"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="mob-drawer-body">
        <div class="mob-drawer-row">
            <span>წაშლილი კატეგორიები</span>
            <label class="switch mb-0">
                <input type="checkbox" id="mob-toggle-deleted">
                <span class="switch-slider"></span>
            </label>
        </div>
        <div class="mob-drawer-row">
            <span>ჩანაწ. რაოდ.</span>
            <select id="mob-dt-page-length" style="width:90px;height:34px;font-size:13px;border-radius:8px;border:1.5px solid #e2e8f0;padding:0 8px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="-1">ყველა</option>
            </select>
        </div>
    </div>
</div>
<div class="mob-drawer-backdrop" id="cat-filter-backdrop" onclick="toggleCatDrawer()"></div>
@endif

<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-tags me-2" style="color:#f59e0b;"></i>კატეგორიები</h2>
            <p class="mod-subtitle">პროდუქტების კატეგორიების მართვა</p>
        </div>
        <div class="mod-actions">
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'categories', 'can_create'))
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i> ახალი
            </button>
            @endif
            <a href="{{ route('exportPDF.categoriesAll') }}" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca;">
                <i class="fa fa-file-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
            </a>
            <a href="{{ route('exportExcel.categoriesAll') }}" class="btn btn-sm" style="background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;">
                <i class="fa fa-file-excel me-1"></i><span class="d-none d-sm-inline">Excel</span>
            </a>
        </div>
    </div>

    {{-- ── MOBILE ACTION BAR ── --}}
    <div class="cat-mob-action-bar">
        @if(\App\Models\RolePermission::check(auth()->user()->role, 'categories', 'can_create'))
        <button onclick="addForm()" class="cat-mab-btn cat-mab-primary">
            <i class="fa fa-plus"></i> ახალი
        </button>
        <div class="cat-mab-sep"></div>
        @endif
        <a href="{{ route('exportPDF.categoriesAll') }}" class="cat-mab-btn cat-mab-ghost">
            <i class="fa fa-file-pdf" style="color:#ef4444;"></i> PDF
        </a>
        <a href="{{ route('exportExcel.categoriesAll') }}" class="cat-mab-btn cat-mab-ghost">
            <i class="fa fa-file-excel" style="color:#16a34a;"></i> Excel
        </a>
    </div>

    <div class="mod-card">
        <div class="mod-toolbar">
            <select id="dt-page-length" class="form-select form-select-sm" style="width:75px;">
                <option value="10">10</option><option value="25">25</option>
                <option value="50">50</option><option value="100">100</option>
                <option value="-1">ყველა</option>
            </select>
            <div class="mod-toolbar-search">
                <i class="fa fa-search search-icon"></i>
                <input id="dt-search" type="search" class="form-control form-control-sm" placeholder="ძებნა...">
            </div>
            <div class="mod-spacer"></div>
            @if(Auth::user()->role === 'admin')
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:12px;color:#94a3b8;font-weight:500;">Deleted</span>
                <label class="switch mb-0">
                    <input type="checkbox" id="toggle-deleted">
                    <span class="switch-slider"></span>
                </label>
            </div>
            @endif
        </div>
        <div class="table-responsive">
            <table id="categories-table" class="table table-hover w-100">
                <thead><tr>
                    <th>სახელი</th><th>ზომები</th><th>სტატუსი</th><th>მოქმედება</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

@include('categories.form')
@endsection

@section('bot')
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
<script>
var showingDeleted = false;

var table = $('#categories-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('api.categories') }}",
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
    pageLength: 10,
    autoWidth: false,
    columns: [
        {data: 'name', name: 'name', width: '30%', render: function(data, type, row) {
            if (type === 'display' && row.is_divisible) {
                return '<span style="font-weight:600;">' + $('<div>').text(data).html() + '</span> <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;margin-left:4px;">დაშლადი</span>';
            }
            return $('<div>').text(data).html();
        }},
        {data: 'sizes_display',  name: 'sizes_display',  orderable: false, searchable: false, width: '45%'},
        {data: 'status_display', name: 'status_display', orderable: false, searchable: false, visible: false, width: '60px'},
        {data: 'action',         name: 'action',         orderable: false, searchable: false, width: '70px'}
    ],
    drawCallback: function() {
        setupCatMobileCards();
    }
});

/* ── Desktop controls ── */
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

/* ── Desktop deleted toggle ── */
$('#toggle-deleted').on('change', function() {
    showingDeleted = $(this).is(':checked');
    if (showingDeleted) {
        table.column(2).visible(true);
        table.ajax.url("{{ route('api.categories.deleted') }}").load();
    } else {
        table.column(2).visible(false);
        table.ajax.url("{{ route('api.categories') }}").load();
    }
    updateCatFilterBadge();
});

/* ── Mobile topbar search ── */
(function() {
    var _t;
    $('#mob-cat-search').on('input', function() {
        clearTimeout(_t); var v = $(this).val();
        _t = setTimeout(function() { table.search(v).draw(); }, 300);
        $('#dt-search').val(v);
    });
})();

/* ── Mobile filter drawer ── */
function toggleCatDrawer() {
    var drawer = $('#cat-filter-drawer');
    var isOpen = drawer.hasClass('open');
    drawer.toggleClass('open', !isOpen);
    $('#cat-filter-backdrop').toggleClass('show', !isOpen);
    $('#cat-filter-btn').toggleClass('active', !isOpen);
    $('body').css('overflow', isOpen ? '' : 'hidden');
}

function updateCatFilterBadge() {
    var count = showingDeleted ? 1 : 0;
    $('#cat-filter-badge').text(count || '');
    $('#cat-filter-btn').toggleClass('has-active', count > 0);
}

/* ── Mobile drawer: deleted toggle ── */
$('#mob-toggle-deleted').on('change', function() {
    $('#toggle-deleted').prop('checked', $(this).is(':checked')).trigger('change');
});
/* Sync desktop → mobile */
$('#toggle-deleted').on('change', function() {
    $('#mob-toggle-deleted').prop('checked', $(this).is(':checked'));
});

/* ── Mobile drawer: page length ── */
$('#mob-dt-page-length').on('change', function() {
    table.page.len(parseInt($(this).val())).draw();
    $('#dt-page-length').val($(this).val());
});

/* ── Mobile card: sizes expand/collapse ── */
function setupCatMobileCards() {
    if (window.innerWidth > 767) return;
    $('#categories-table tbody td:nth-child(2)').each(function() {
        var td = $(this);
        // Wrap content if not already done
        if (!td.find('.cat-sizes-inner').length) {
            var html = td.html();
            td.html('<div class="cat-sizes-inner">' + html + '</div><button type="button" class="cat-sizes-toggle">ყველა ▾</button>');
        }
        // Show toggle only if content overflows one line
        var inner = td.find('.cat-sizes-inner')[0];
        var toggle = td.find('.cat-sizes-toggle');
        if (inner.scrollHeight > inner.clientHeight + 2) {
            toggle.show();
        } else {
            toggle.hide();
        }
    });
}

$(document).on('click', '.cat-sizes-toggle', function() {
    var td = $(this).closest('td');
    var expanded = td.hasClass('cat-expanded');
    td.toggleClass('cat-expanded', !expanded);
    $(this).text(expanded ? 'ყველა ▾' : 'ნაკლები ▴');
});

/* ── CRUD ── */
function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({ title: 'Restore Category?', text: 'კატეგორია დაბრუნდება active სტატუსში', icon: 'warning', showCancelButton: true, confirmButtonText: 'დიახ' })
    .then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('categories') }}/" + id + "/restore",
                type: "POST",
                data: {'_method': 'PATCH', '_token': csrf_token},
                success: function(data) { table.ajax.reload(); swal({ title: 'Restored!', text: data.message, icon: 'success' }); },
                error: function() { swal({ title: 'Error', icon: 'error' }); }
            });
        }
    });
}

function addForm() {
    save_method = "add";
    $('input[name=_method]').val('POST');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
    $('#modal-form form')[0].reset();
    $('.modal-title').text('Add Category');
}

function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#modal-form form')[0].reset();
    $.ajax({
        url: "{{ url('categories') }}/" + id + "/edit",
        type: "GET", dataType: "JSON", cache: false,
        success: function(data) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
            $('.modal-title').text('Edit Category');
            $('#id').val(data.id);
            $('#name').val(data.name);
            $('#sizes').val(data.sizes);
            $('#is_divisible').prop('checked', data.is_divisible == true || data.is_divisible == 1);
        }
    });
}

function deleteData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
    .then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('categories') }}/" + id,
                type: "POST",
                data: {'_method': 'DELETE', '_token': csrf_token},
                success: function(data) { table.ajax.reload(); swal({ title: 'Deleted!', text: data.message, icon: 'success', timer: 1500 }); },
                error: function(data) {
                    var response = data.responseJSON;
                    swal({ title: 'შეცდომა!', text: response.message || 'Error', icon: 'error' });
                }
            });
        }
    });
}

$(function() {
    $('#modal-form form').validator().on('submit', function(e) {
        if (!e.isDefaultPrevented()) {
            var $submitBtn = $(this).find('[type=submit]');
            if ($submitBtn.prop('disabled')) return false;
            $submitBtn.prop('disabled', true).css('opacity', '0.65');
            var id  = $('#id').val();
            var url = (save_method == 'add') ? "{{ url('categories') }}" : "{{ url('categories') }}/" + id;
            $.ajax({
                url: url, type: "POST",
                data: new FormData($("#modal-form form")[0]),
                contentType: false, processData: false,
                success: function(data) {
                    bootstrap.Modal.getInstance(document.getElementById('modal-form')).hide();
                    table.ajax.reload();
                    swal({ title: 'Success!', text: data.message, icon: 'success', timer: 1500 });
                },
                error: function(data) {
                    var response = JSON.parse(data.responseText);
                    var errorString = '';
                    if (data.status === 422) {
                        $.each(response.errors, function(key, value) { errorString += value + ' '; });
                    } else { errorString = 'Something went wrong!'; }
                    swal({ title: 'Oops...', text: errorString, icon: 'error' });
                },
                complete: function() { $submitBtn.prop('disabled', false).css('opacity', ''); }
            });
            return false;
        }
    });
});
</script>
@endsection
