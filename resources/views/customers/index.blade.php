@extends('layouts.master')
@section('page_title')<i class="fa fa-users me-2" style="color:#27ae60;"></i>მომხმარებლები@endsection

@section('top')
<style>
/* ── Design tokens ── */
:root {
    --cust-surface:    #ffffff;
    --cust-surface2:   #f6f7fb;
    --cust-border:     rgba(99,115,150,.20);
    --cust-text-1:     #1a2235;
    --cust-text-2:     #3d4a5c;
    --cust-text-3:     #7a8a9e;
    --cust-green:      #059669;
    --cust-green-dim:  #f0fdf4;
    --cust-r-md:       12px;
    --cust-r-sm:       8px;
    --cust-sh-sm:      0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03);
}

/* ── Desktop ── */
.app-container { font-size: 0.85rem !important; }
.table { font-size: 0.82rem !important; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.form-control-sm, .form-select-sm { font-size: 0.82rem; }
.was-validated .form-control:invalid, .was-validated .form-select:invalid { border-color: #dc3545 !important; }
.invalid-feedback { font-size: 0.75rem; }

/* Mobile-only: hidden on desktop */
.cust-mob-action-bar  { display: none; }
.cust-mob-search-wrap { display: none; }
.cust-mob-cell        { display: none !important; }

/* ══════════════════════════════════════════════
   MOBILE CARD VIEW  ≤767px
══════════════════════════════════════════════ */
@media (max-width: 767px) {

    .mod-header  { display: none !important; }
    .mod-toolbar { display: none !important; }
    .mod-wrap { padding: 8px 12px 70px !important; }
    .mod-card {
        background: transparent !important;
        border: none !important; box-shadow: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }
    .table-responsive { overflow: visible !important; }

    /* ── Mobile action bar ── */
    .cust-mob-action-bar {
        display: flex; flex-wrap: nowrap; align-items: center; gap: 6px;
        padding: 0 0 8px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .cust-mob-action-bar::-webkit-scrollbar { display: none; }

    /* ── Mobile search bar ── */
    .cust-mob-search-wrap { display: block; margin-bottom: 10px; position: relative; }
    .cust-mob-search {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid var(--cust-border);
        border-radius: 14px; padding: 10px 6px 10px 14px;
        box-shadow: var(--cust-sh-sm);
    }
    .cust-mob-search > i { font-size: 13px; color: var(--cust-text-3); flex-shrink: 0; }
    .cust-mob-search input {
        flex: 1; min-width: 0; background: none; border: none; outline: none;
        font-size: 14px; color: var(--cust-text-1); font-family: inherit;
    }
    .cust-mob-search input::placeholder { color: var(--cust-text-3); font-size: 13px; }
    #cust-filter-btn-inline.has-active .mob-ts-filter-badge { display: flex !important; }

    /* ── Inline filter panel ── */
    @keyframes custPanelIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .cust-filter-panel {
        display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid var(--cust-border);
        border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.12); z-index: 200;
        overflow: hidden;
    }
    .cust-filter-panel.open { display: block; animation: custPanelIn .18s ease; }
    .cust-filter-panel-body { display: flex; flex-direction: column; padding: 12px 16px 14px; gap: 10px; }
    .cust-fp-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 3px; }
    .cust-filter-panel select {
        width: 100%; height: 40px; font-size: 13.5px;
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0 10px;
        background: #fff; color: #1e293b; font-family: inherit;
    }

    /* ── Action bar buttons ── */
    .cust-mab-btn {
        display: inline-flex; align-items: center; gap: 5px;
        white-space: nowrap; cursor: pointer; font-family: inherit;
        font-size: 12.5px; font-weight: 600; line-height: 1;
        padding: 7px 13px; border-radius: 22px; border: 1.5px solid transparent;
        touch-action: manipulation; -webkit-tap-highlight-color: transparent;
        transition: background .15s, transform .1s; flex-shrink: 0; text-decoration: none;
    }
    .cust-mab-btn:active { transform: scale(.93); }
    .cust-mab-btn > i { font-size: 10px; }
    .cust-mab-primary { background: var(--cust-green); border-color: var(--cust-green); color: #fff; box-shadow: 0 2px 10px rgba(5,150,105,.28); }
    .cust-mab-pdf     { background: #fef2f2; border-color: #fecaca; color: #ef4444; }
    .cust-mab-excel   { background: #f0fdf4; border-color: #bbf7d0; color: #059669; }

    /* ── Table → block ── */
    #customer-table       { display: block !important; width: 100% !important; }
    #customer-table thead { display: none !important; }
    #customer-table tbody { display: block !important; }
    #customer-table tfoot { display: none !important; }

    /* ── Row = card ── */
    #customer-table tbody tr {
        display: block !important;
        background: var(--cust-surface) !important;
        border-radius: var(--cust-r-md) !important;
        margin: 0 0 10px !important;
        box-shadow: var(--cust-sh-sm) !important;
        border: 1px solid var(--cust-border) !important;
        overflow: hidden;
    }
    #customer-table tbody tr td:not(.cust-mob-cell) { display: none !important; }
    .cust-mob-cell { display: block !important; padding: 0 !important; border: none !important; }

    /* ── Card structure ── */
    .cust-mob-card { display: flex; flex-direction: column; }
    .cust-mob-hdr {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 10px 10px 12px;
    }

    .cust-mob-info { flex: 1; min-width: 0; }
    .cust-mob-name {
        font-size: 13.5px; font-weight: 700; color: var(--cust-text-1);
        line-height: 1.3; word-break: break-word;
    }
    .cust-mob-contact {
        font-size: 12.5px; color: var(--cust-text-2); margin-top: 3px; font-weight: 500;
    }
    .cust-mob-contact a { color: var(--cust-text-2); text-decoration: none; }
    .cust-mob-meta {
        display: flex; flex-wrap: wrap; gap: 4px; align-items: center; margin-top: 5px;
    }
    .cust-meta-item {
        font-size: 11px; color: var(--cust-text-2); white-space: nowrap;
        background: var(--cust-surface2); border: 1px solid var(--cust-border);
        border-radius: 5px; padding: 1px 6px; line-height: 1.6;
    }
    .cust-mob-act {
        flex-shrink: 0; display: flex; gap: 4px; align-items: flex-start; margin-left: auto;
    }
    .cust-mob-act .btn-xs {
        padding: 5px 8px !important; font-size: 11px !important;
        border-radius: 7px !important; touch-action: manipulation;
    }

    /* Comment footer */
    .cust-mob-comment {
        padding: 6px 12px 8px; border-top: 1px solid var(--cust-border);
        font-size: 11.5px; color: #64748b; font-style: italic;
    }
    .cust-mob-comment i { margin-right: 4px; opacity: .6; }
}
</style>
@endsection

@section('content')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-users me-2" style="color:#27ae60;"></i>მომხმარებლები</h2>
            <p class="mod-subtitle">კლიენტების მართვა</p>
        </div>
        <div class="mod-actions">
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'customers', 'can_create'))
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">დამატება</span>
            </button>
            @endif
            <a href="{{ route('exportPDF.customersAll') }}" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca;">
                <i class="fa fa-file-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
            </a>
            <a href="{{ route('exportExcel.customersAll') }}" class="btn btn-sm" style="background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;">
                <i class="fa fa-file-excel me-1"></i><span class="d-none d-sm-inline">Excel</span>
            </a>
        </div>
    </div>

    {{-- ── MOBILE ACTION BAR ── --}}
    <div class="cust-mob-action-bar">
        @if(\App\Models\RolePermission::check(auth()->user()->role, 'customers', 'can_create'))
        <button onclick="addForm()" class="cust-mab-btn cust-mab-primary">
            <i class="fa fa-plus"></i> ახალი
        </button>
        @endif
        <a href="{{ route('exportPDF.customersAll') }}" class="cust-mab-btn cust-mab-pdf">
            <i class="fa fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('exportExcel.customersAll') }}" class="cust-mab-btn cust-mab-excel">
            <i class="fa fa-file-excel"></i> Excel
        </a>
    </div>

    {{-- ── MOBILE SEARCH + FILTER PANEL ── --}}
    <div class="cust-mob-search-wrap">
        <div class="cust-mob-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" id="mob-cust-search" placeholder="სახელი, ტელ, მისამართი..." autocomplete="off">
            <button type="button" class="mob-ts-filter-btn" id="cust-filter-btn-inline" onclick="toggleCustPanel()">
                <i class="fa fa-sliders"></i>
                <span class="mob-ts-filter-badge" id="cust-filter-badge"></span>
            </button>
        </div>
        <div class="cust-filter-panel" id="cust-filter-panel">
            <div class="cust-filter-panel-body">
                <div>
                    <div class="cust-fp-label">ქალაქი</div>
                    <select id="mob-filter-city">
                        <option value="">— ყველა ქალაქი —</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="cust-fp-label">ჩანაწ. რაოდ.</div>
                    <select id="mob-dt-page-length">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
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
            </select>
            <div class="mod-toolbar-search">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="dt-search" class="form-control form-control-sm" placeholder="ძებნა...">
            </div>
            <select id="filter_city" class="form-select form-select-sm" style="width:150px;">
                <option value="">ყველა ქალაქი</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="table-responsive">
            <table id="customer-table" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>სახელი</th>
                        <th>ქალაქი</th>
                        <th>მისამართი</th>
                        <th>Email</th>
                        <th>კონტაქტი</th>
                        <th>შენიშვნა</th>
                        <th class="text-center">მოქმედება</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

@include('customers.form')
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$('#modal-form').on('show.bs.modal', function () {
    if (!$('#city_id').data('select2')) {
        $('#city_id').select2({
            dropdownParent: $('#modal-form'),
            placeholder: '-- ქალაქის ძიება --',
            allowClear: true,
            language: { noResults: function () { return 'ქალაქი ვერ მოიძებნა'; } }
        });
    }
});
$('#modal-form').on('shown.bs.modal', function () {
    $('#city_id').trigger('change');
});
</script>
<script>

var table = $('#customer-table').DataTable({
    processing: true,
    serverSide: true,
    paging: true,
    pageLength: 25,
    order: [],
    ajax: {
        url: "{{ route('api.customers') }}",
        data: function(d) { d.city_id = $('#filter_city').val(); }
    },
    columns: [
        {data:'id'}, {data:'name'}, {data:'city_name'}, {data:'address'},
        {data:'email'}, {data:'contact_info'}, {data:'comment'},
        {data:'action', orderable:false, searchable:false}
    ],
    language: {
        processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
        info: '_START_–_END_ of _TOTAL_',
        paginate: { previous: '‹', next: '›' }
    },
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
    drawCallback: function() {
        buildMobileCustCards();
    }
});

/* ── Desktop controls ── */
$('#dt-search').on('keyup', function() { table.search(this.value).draw(); });
$('#dt-page-length').on('change', function() { table.page.len(parseInt(this.value)).draw(); });
$('#filter_city').on('change', function() { table.draw(); });

/* ── Rebuild cards on desktop→mobile switch ── */
(function() {
    var _mob = window.innerWidth <= 767, _t;
    $(window).on('resize', function() {
        clearTimeout(_t);
        _t = setTimeout(function() {
            var now = window.innerWidth <= 767;
            if (now && !_mob) buildMobileCustCards();
            _mob = now;
        }, 200);
    });
})();

/* ── Mobile search ── */
(function() {
    var _t;
    $('#mob-cust-search').on('input', function() {
        clearTimeout(_t); var v = $(this).val();
        _t = setTimeout(function() { table.search(v).draw(); }, 300);
        $('#dt-search').val(v);
    });
})();

/* ── Mobile filter panel ── */
function toggleCustPanel() {
    var panel  = $('#cust-filter-panel');
    var isOpen = panel.hasClass('open');
    panel.toggleClass('open', !isOpen);
    $('#cust-filter-btn-inline').toggleClass('active', !isOpen);
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('.cust-mob-search-wrap').length) {
        $('#cust-filter-panel').removeClass('open');
        $('#cust-filter-btn-inline').removeClass('active');
    }
});

function updateCustFilterBadge() {
    var count = $('#filter_city').val() ? 1 : 0;
    $('#cust-filter-badge').text(count || '');
    $('#cust-filter-btn-inline').toggleClass('has-active', count > 0);
}

/* Mobile ↔ Desktop sync */
$('#mob-filter-city').on('change', function() {
    $('#filter_city').val($(this).val()).trigger('change');
    updateCustFilterBadge();
});
$('#filter_city').on('change', function() {
    $('#mob-filter-city').val($(this).val());
    updateCustFilterBadge();
});
$('#mob-dt-page-length').on('change', function() {
    table.page.len(parseInt($(this).val())).draw();
    $('#dt-page-length').val($(this).val());
});

/* ── Build mobile cards ── */
function buildMobileCustCards() {
    if (window.innerWidth > 767) return;
    $('#customer-table tbody tr').each(function() {
        if ($(this).find('.cust-mob-cell').length) return;

        var tr  = $(this);
        var tds = tr.find('td');
        if (tds.length < 8) return;

        var name    = $.trim(tds.eq(1).text()) || '';
        var city    = $.trim(tds.eq(2).text()) || '';
        var address = $.trim(tds.eq(3).text()) || '';
        var email   = $.trim(tds.eq(4).text()) || '';
        var contact = tds.eq(5).html() || '';
        var comment = $.trim(tds.eq(6).text()) || '';
        var action  = tds.eq(7).html() || '';

        function notEmpty(v) { return v && v !== '-' && v !== '—' && v !== '–'; }

        /* Meta chips */
        var meta = '';
        if (notEmpty(city))    meta += '<span class="cust-meta-item"><i class="fa fa-location-dot" style="font-size:9px;opacity:.6;margin-right:3px;"></i>' + $('<div>').text(city).html() + '</span>';
        if (notEmpty(address)) meta += '<span class="cust-meta-item">' + $('<div>').text(address).html() + '</span>';
        if (notEmpty(email))   meta += '<span class="cust-meta-item"><i class="fa fa-envelope" style="font-size:9px;opacity:.6;margin-right:3px;"></i>' + $('<div>').text(email).html() + '</span>';

        /* Comment */
        var commentSection = notEmpty(comment)
            ? '<div class="cust-mob-comment"><i class="fa fa-comment-dots"></i>' + $('<div>').text(comment).html() + '</div>'
            : '';

        var card = '<div class="cust-mob-card">'
            + '<div class="cust-mob-hdr">'
            +   '<div class="cust-mob-info">'
            +     '<div class="cust-mob-name">' + $('<div>').text(name).html() + '</div>'
            +     (notEmpty(contact) ? '<div class="cust-mob-contact">' + contact + '</div>' : '')
            +     (meta ? '<div class="cust-mob-meta">' + meta + '</div>' : '')
            +   '</div>'
            +   '<div class="cust-mob-act">' + action + '</div>'
            + '</div>'
            + commentSection
            + '</div>';

        tr.append('<td class="cust-mob-cell" colspan="8">' + card + '</td>');
    });
}

/* ── Forms ── */
function addForm() {
    save_method = "add";
    $('input[name=_method]').val('POST');
    $('#modal-form form')[0].reset();
    $('#city_id').val('');
    $('#modal-form form').removeClass('was-validated');
    $('.modal-title').text('კლიენტის დამატება');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
}

function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#modal-form form').removeClass('was-validated');
    $.ajax({
        url: "{{ url('customers') }}/" + id + "/edit",
        type: "GET",
        dataType: "JSON",
        cache: false,
        success: function(data) {
            $('.modal-title').text('რედაქტირება');
            $('#id').val(data.id); $('#name').val(data.name); $('#address').val(data.address);
            $('#email').val(data.email); $('#city_id').val(data.city_id);
            $('#tel').val(data.tel); $('#alternative_tel').val(data.alternative_tel);
            $('#comment').val(data.comment);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
        }
    });
}

function deleteData(id) {
    Swal.fire({
        title: 'დარწმუნებული ხართ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'წაშლა',
        cancelButtonText: 'გაუქმება'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('customers') }}/" + id,
                type: "POST",
                data: {'_method':'DELETE','_token':'{{ csrf_token() }}'},
                success: function() {
                    table.ajax.reload();
                    Swal.fire('წაიშალა!', '', 'success');
                }
            });
        }
    });
}

$(function() {
    $('#modal-form form').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('was-validated');
            return false;
        }

        var $submitBtn = $(this).find('[type=submit]');
        if ($submitBtn.prop('disabled')) return false;
        $submitBtn.prop('disabled', true).css('opacity', '0.65');

        var id  = $('#id').val();
        var url = (save_method == 'add') ? "{{ url('customers') }}" : "{{ url('customers') }}/" + id;
        var form = this;

        $.ajax({
            url: url,
            type: "POST",
            data: new FormData(form),
            contentType: false,
            processData: false,
            success: function(data) {
                bootstrap.Modal.getInstance(document.getElementById('modal-form')).hide();
                table.ajax.reload();
                Swal.fire({ title: 'წარმატება!', text: data.message, icon: 'success', timer: 1500 });
            },
            error: function(data) {
                if (data.status === 422) {
                    var errors = data.responseJSON.errors;
                    var errorMessages = "";
                    $.each(errors, function(key, value) { errorMessages += value[0] + "<br>"; });
                    Swal.fire({ title: 'ვალიდაციის შეცდომა!', html: errorMessages, icon: 'error' });
                } else {
                    Swal.fire({ title: 'Oops!', text: 'დაფიქსირდა გაუთვალისწინებელი შეცდომა', icon: 'error' });
                }
            },
            complete: function() { $submitBtn.prop('disabled', false).css('opacity', ''); }
        });
        return false;
    });
});
</script>
@endsection
