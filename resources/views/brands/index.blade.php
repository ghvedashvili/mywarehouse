@extends('layouts.master')
@section('page_title')<i class="fa fa-copyright me-2" style="color:#8b5cf6;"></i>ბრენდები@endsection

@section('top')
<style>
.switch { position: relative; display: inline-block; width: 46px; height: 24px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
.switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }

#brands-table { table-layout: fixed; width: 100% !important; word-wrap: break-word; }
#brands-table td { white-space: normal !important; word-break: break-word; vertical-align: middle; }
#brands-table td:last-child { white-space: nowrap !important; }
.table-responsive { overflow-x: hidden; }

/* Mobile-only: hidden on desktop */
.brand-mob-bar        { display: none; }
.brand-mob-search-wrap { display: none; }
.brand-mob-cell       { display: none; }

@media (max-width: 767px) {
    .mod-header  { display: none !important; }
    .mod-toolbar { display: none !important; }
    .mod-wrap { padding: 8px 12px 70px !important; }
    .mod-card { background: transparent !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; }
    .table-responsive { overflow: visible !important; }

    /* ── Mobile action bar ── */
    .brand-mob-bar {
        display: flex; align-items: center; gap: 8px;
        padding: 0 0 8px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .brand-mob-bar::-webkit-scrollbar { display: none; }
    .brand-mob-add {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px;
        padding: 7px 13px; border-radius: 22px; border: none;
        background: #059669; color: #fff; font-size: 12.5px; font-weight: 600;
        box-shadow: 0 2px 10px rgba(5,150,105,.28);
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
        cursor: pointer;
    }
    .brand-mob-add:active { transform: scale(.93); }
    .brand-mob-add > i { font-size: 10px; }

    /* ── Mobile search + filter ── */
    .brand-mob-search-wrap { display: block; margin-bottom: 10px; position: relative; }
    .brand-mob-search {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid rgba(99,115,150,.20);
        border-radius: 14px; padding: 10px 6px 10px 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .brand-mob-search > i { font-size: 13px; color: #7a8a9e; flex-shrink: 0; }
    .brand-mob-search input {
        flex: 1; min-width: 0; background: none; border: none; outline: none;
        font-size: 14px; color: #1a2235; font-family: inherit;
    }
    .brand-mob-search input::placeholder { color: #7a8a9e; font-size: 13px; }
    #brand-filter-btn.has-active .mob-ts-filter-badge { display: flex !important; }

    /* ── Inline filter panel ── */
    @keyframes brandPanelIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .brand-filter-panel {
        display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid rgba(99,115,150,.20);
        border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.12); z-index: 200;
        overflow: hidden;
    }
    .brand-filter-panel.open { display: block; animation: brandPanelIn .18s ease; }
    .brand-filter-panel-body { display: flex; flex-direction: column; padding: 12px 16px 14px; gap: 12px; }
    .brand-fp-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 3px; }
    .brand-filter-panel select {
        width: 100%; height: 40px; font-size: 13.5px;
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0 10px;
        background: #fff; color: #1e293b; font-family: inherit;
    }
    .brand-fp-toggle-row {
        display: flex; align-items: center; justify-content: space-between;
    }

    /* ── Table → block ── */
    #brands-table       { display: block !important; width: 100% !important; }
    #brands-table thead { display: none !important; }
    #brands-table tbody { display: block !important; }
    #brands-table tfoot { display: none !important; }

    /* ── Row = card ── */
    #brands-table tbody tr {
        display: block !important;
        background: #fff !important;
        border-radius: 12px !important;
        margin: 0 0 8px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03) !important;
        border: none !important;
        overflow: hidden;
    }
    #brands-table tbody tr td:not(.brand-mob-cell) { display: none !important; }
    .brand-mob-cell { display: block !important; padding: 0 !important; border: none !important; width: 100%; }

    /* ── Card: single row ── */
    .brand-mob-card {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; width: 100%;
    }
    .brand-mob-logo-img {
        flex-shrink: 0;
        width: 42px; height: 42px; border-radius: 8px;
        object-fit: contain; border: 1px solid rgba(99,115,150,.15);
        background: #f8f9fb; padding: 3px;
    }
    .brand-mob-logo-placeholder {
        flex-shrink: 0;
        width: 42px; height: 42px; border-radius: 8px;
        background: #f1f3f9; border: 1px solid rgba(99,115,150,.15);
        display: flex; align-items: center; justify-content: center;
        color: #b0b8c8; font-size: 18px;
    }
    .brand-mob-name {
        flex: 1; min-width: 0;
        font-size: 14px; font-weight: 700; color: #1a2235;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .brand-mob-ftr { flex-shrink: 0; display: flex; gap: 4px; align-items: center; margin-left: auto; }
}
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-copyright me-2" style="color:#8b5cf6;"></i>ბრენდები</h2>
            <p class="mod-subtitle">პროდუქტების ბრენდების მართვა</p>
        </div>
        <div class="mod-actions">
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'brands', 'can_create'))
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i> ახალი
            </button>
            @endif
        </div>
    </div>

    {{-- ── MOBILE ACTION BAR ── --}}
    <div class="brand-mob-bar">
        @if(\App\Models\RolePermission::check(auth()->user()->role, 'brands', 'can_create'))
        <button type="button" class="brand-mob-add" onclick="addForm()">
            <i class="fa fa-plus"></i> ახალი
        </button>
        @endif
    </div>

    {{-- ── MOBILE SEARCH + FILTER ── --}}
    <div class="brand-mob-search-wrap">
        <div class="brand-mob-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" id="mob-brand-search" placeholder="ბრენდის სახელი..." autocomplete="off">
            <button type="button" class="mob-ts-filter-btn" id="brand-filter-btn" onclick="toggleBrandPanel()">
                <i class="fa fa-sliders"></i>
                <span class="mob-ts-filter-badge" id="brand-filter-badge"></span>
            </button>
        </div>
        <div class="brand-filter-panel" id="brand-filter-panel">
            <div class="brand-filter-panel-body">
                <div>
                    <div class="brand-fp-label">ჩანაწ. რაოდ.</div>
                    <select id="mob-dt-page-length">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">ყველა</option>
                    </select>
                </div>
                @if(Auth::user()->role === 'admin')
                <div class="brand-fp-toggle-row">
                    <span class="brand-fp-label mb-0">წაშლილი ბრენდები</span>
                    <label class="switch mb-0">
                        <input type="checkbox" id="mob-toggle-deleted">
                        <span class="switch-slider"></span>
                    </label>
                </div>
                @endif
            </div>
        </div>
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
            <table id="brands-table" class="table table-hover w-100">
                <thead><tr>
                    <th style="width:70px;">ლოგო</th>
                    <th>სახელი</th>
                    <th style="width:80px;display:none;">სტატუსი</th>
                    <th style="width:90px;">მოქმედება</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

@include('brands.form')
@endsection

@section('bot')
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script>
var showingDeleted = false;

var table = $('#brands-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('api.brands') }}",
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
    pageLength: 10,
    autoWidth: false,
    columns: [
        {data: 'logo_display',   name: 'logo_display',   orderable: false, searchable: false, width: '70px'},
        {data: 'name',           name: 'name'},
        {data: 'status_display', name: 'status_display', orderable: false, searchable: false, visible: false, width: '80px'},
        {data: 'action',         name: 'action',         orderable: false, searchable: false, width: '90px'}
    ],
    drawCallback: function() { buildMobileBrandCards(this.api()); }
});

$('#dt-page-length').on('change', function() {
    table.page.len(parseInt($(this).val())).draw();
});

$('#dt-search').on('input', function() {
    table.search($(this).val()).draw();
});

/* ── Rebuild cards on desktop→mobile switch ── */
(function() {
    var _mob = window.innerWidth <= 767, _t;
    $(window).on('resize', function() {
        clearTimeout(_t);
        _t = setTimeout(function() {
            var now = window.innerWidth <= 767;
            if (now && !_mob) buildMobileBrandCards(table.api());
            _mob = now;
        }, 200);
    });
})();

/* ── Mobile search sync ── */
(function() {
    var _t;
    $('#mob-brand-search').on('input', function() {
        clearTimeout(_t); var v = $(this).val();
        _t = setTimeout(function() { table.search(v).draw(); }, 300);
        $('#dt-search').val(v);
    });
})();

/* ── Mobile filter panel ── */
function toggleBrandPanel() {
    var panel  = $('#brand-filter-panel');
    var isOpen = panel.hasClass('open');
    panel.toggleClass('open', !isOpen);
    $('#brand-filter-btn').toggleClass('active', !isOpen);
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('.brand-mob-search-wrap').length) {
        $('#brand-filter-panel').removeClass('open');
        $('#brand-filter-btn').removeClass('active');
    }
});

function updateBrandFilterBadge() {
    var count = ($('#mob-toggle-deleted').is(':checked') ? 1 : 0);
    $('#brand-filter-badge').text(count || '');
    $('#brand-filter-btn').toggleClass('has-active', count > 0);
}

/* Mobile page length */
$('#mob-dt-page-length').on('change', function() {
    var v = parseInt($(this).val());
    table.page.len(v).draw();
    $('#dt-page-length').val($(this).val());
});

/* Mobile deleted toggle — syncs with desktop #toggle-deleted */
$('#mob-toggle-deleted').on('change', function() {
    $('#toggle-deleted').prop('checked', $(this).is(':checked')).trigger('change');
    updateBrandFilterBadge();
});

/* ── Build mobile brand cards ── */
function buildMobileBrandCards(api) {
    if (window.innerWidth > 767) return;
    api.rows().every(function() {
        var tr = $(this.node());
        if (tr.find('.brand-mob-cell').length) return;

        var data = this.data();
        var logoHtml   = data.logo_display || '';
        var name       = data.name         || '';
        var actionHtml = data.action       || '';

        var imgMatch = logoHtml.match(/<img[^>]+src=["']([^"']+)["']/i);
        var logoEl = imgMatch
            ? '<img src="' + imgMatch[1] + '" class="brand-mob-logo-img" alt="">'
            : '<div class="brand-mob-logo-placeholder"><i class="fa fa-copyright"></i></div>';

        var card = '<div class="brand-mob-card">'
            + logoEl
            + '<span class="brand-mob-name">' + $('<div>').text(name).html() + '</span>'
            + '<div class="brand-mob-ftr">' + actionHtml + '</div>'
            + '</div>';

        tr.append('<td class="brand-mob-cell" colspan="4">' + card + '</td>');
    });
}

$('#toggle-deleted').on('change', function() {
    showingDeleted = $(this).is(':checked');
    if (showingDeleted) {
        table.column(2).visible(true);
        table.ajax.url("{{ route('api.brands.deleted') }}").load();
    } else {
        table.column(2).visible(false);
        table.ajax.url("{{ route('api.brands') }}").load();
    }
});

function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({ title: 'Restore Brand?', text: 'ბრენდი დაბრუნდება active სტატუსში', icon: 'warning', showCancelButton: true, confirmButtonText: 'დიახ' })
    .then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('brands') }}/" + id + "/restore",
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
    $('#modal-form form')[0].reset();
    document.getElementById('brand-logo-preview').innerHTML = '<span class="text-muted small"><i class="fa fa-image"></i></span>';
    $('.modal-title').text('ბრენდის დამატება');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
}

function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#form-item')[0].reset();
    $.ajax({
        url: "{{ url('brands') }}/" + id + "/edit",
        type: "GET", dataType: "JSON", cache: false,
        success: function(data) {
            $('.modal-title').text('ბრენდის რედაქტირება');
            $('#id').val(data.id);
            $('#name').val(data.name);
            var preview = document.getElementById('brand-logo-preview');
            if (data.logo_url) {
                preview.innerHTML = '<img src="' + data.logo_url + '" style="max-height:68px;max-width:100%;object-fit:contain;border-radius:4px;">';
            } else {
                preview.innerHTML = '<span class="text-muted small"><i class="fa fa-image"></i></span>';
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
        },
        error: function(xhr) {
            console.error('brand edit error:', xhr.status, xhr.responseText);
        }
    });
}

function deleteData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
    .then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('brands') }}/" + id,
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
            var url = (save_method == 'add') ? "{{ url('brands') }}" : "{{ url('brands') }}/" + id;
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
