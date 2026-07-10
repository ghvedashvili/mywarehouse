@extends('layouts.master')
@section('page_title')<i class="fa fa-cubes me-2" style="color:#0ea5e9;"></i>კომპლექტები@endsection

@section('top')
<style>
/* Mobile-only: hidden on desktop */
.bun-mob-bar        { display: none; }
.bun-mob-search-wrap { display: none; }
.bun-mob-cell       { display: none; }

@media (max-width: 767px) {
    .mod-header  { display: none !important; }
    .mod-wrap { padding: 8px 12px 70px !important; }
    .mod-card { background: transparent !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; }
    .mod-toolbar { display: none !important; }
    .table-responsive { overflow: visible !important; }

    /* ── Mobile action bar ── */
    .bun-mob-bar {
        display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
    }
    .bun-mob-add {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px;
        padding: 10px 14px; border-radius: 14px; border: none;
        background: #0ea5e9; color: #fff; font-size: 13px; font-weight: 600;
        box-shadow: 0 2px 10px rgba(14,165,233,.28);
        touch-action: manipulation; -webkit-tap-highlight-color: transparent; cursor: pointer;
    }
    .bun-mob-add:active { transform: scale(.93); }

    /* ── Mobile search wrap ── */
    .bun-mob-search-wrap { display: block; margin-bottom: 10px; position: relative; }
    .bun-mob-search {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid rgba(99,115,150,.20);
        border-radius: 14px; padding: 10px 6px 10px 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .bun-mob-search > i { font-size: 13px; color: #7a8a9e; flex-shrink: 0; }
    .bun-mob-search input {
        flex: 1; min-width: 0; background: none; border: none; outline: none;
        font-size: 14px; color: #1a2235; font-family: inherit;
    }
    .bun-mob-search input::placeholder { color: #7a8a9e; font-size: 13px; }
    #bun-filter-btn.active, #bun-filter-btn.has-active { color: inherit !important; background: transparent !important; }
    #bun-filter-btn.has-active .mob-ts-filter-badge { display: flex !important; }

    /* ── Filter panel ── */
    @keyframes bunPanelIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .bun-filter-panel {
        display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid rgba(99,115,150,.20);
        border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.12); z-index: 200;
        overflow: hidden;
    }
    .bun-filter-panel.open { display: block; animation: bunPanelIn .18s ease; }
    .bun-filter-panel-body { display: flex; flex-direction: column; padding: 12px 16px 14px; gap: 10px; }
    .bun-fp-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 3px; }
    .bun-filter-panel select {
        width: 100%; height: 40px; font-size: 13.5px;
        border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 0 10px;
        background: #fff; color: #1e293b; font-family: inherit;
    }

    /* ── Table → block ── */
    #bundles-table       { display: block !important; width: 100% !important; }
    #bundles-table thead { display: none !important; }
    #bundles-table tbody { display: block !important; }
    #bundles-table tfoot { display: none !important; }

    /* ── Row = card ── */
    #bundles-table tbody tr {
        display: block !important;
        background: #fff !important;
        border-radius: 12px !important;
        margin: 0 0 8px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.03) !important;
        border: none !important;
        overflow: hidden;
    }
    #bundles-table tbody tr td:not(.bun-mob-cell) { display: none !important; }
    .bun-mob-cell { display: block !important; padding: 0 !important; border: none !important; width: 100%; }

    /* ── Card structure ── */
    .bun-mob-card { display: flex; flex-direction: column; padding: 10px 12px 8px; gap: 6px; }
    .bun-mob-hdr {
        display: flex; align-items: center; gap: 8px;
    }
    .bun-mob-name {
        flex: 1; min-width: 0;
        font-size: 14px; font-weight: 700; color: #1a2235; line-height: 1.3;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .bun-mob-act { flex-shrink: 0; display: flex; gap: 4px; align-items: center; }
    .bun-mob-products { display: flex; flex-wrap: wrap; gap: 4px; }
}
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-cubes me-2" style="color:#0ea5e9;"></i>კომპლექტები</h2>
            <p class="mod-subtitle">პროდუქტების კომპლექტების მართვა</p>
        </div>
        <div class="mod-actions">
            @if(\App\Models\RolePermission::check(auth()->user()->role, 'product_bundles', 'can_create'))
            <button onclick="openCreateModal()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i> ახალი კომპლექტი
            </button>
            @endif
        </div>
    </div>

    {{-- ── MOBILE ACTION BAR ── --}}
    <div class="bun-mob-bar">
        @if(\App\Models\RolePermission::check(auth()->user()->role, 'product_bundles', 'can_create'))
        <button type="button" class="bun-mob-add" onclick="openCreateModal()">
            <i class="fa fa-plus"></i> ახალი
        </button>
        @endif
    </div>

    {{-- ── MOBILE SEARCH BAR + inline filter panel ── --}}
    <div class="bun-mob-search-wrap">
        <div class="bun-mob-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" id="mob-bun-search" placeholder="კომპლექტის სახელი..." autocomplete="off">
            <button type="button" class="mob-ts-filter-btn" id="bun-filter-btn" onclick="toggleBunDrawer()">
                <i class="fa fa-sliders"></i>
                <span class="mob-ts-filter-badge" id="bun-filter-badge"></span>
            </button>
        </div>
        <div class="bun-filter-panel" id="bun-filter-drawer">
            <div class="bun-filter-panel-body">
                <div>
                    <div class="bun-fp-label">ჩანაწ. რაოდ.</div>
                    <select id="mob-bun-page-length">
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
        </div>
        <div class="table-responsive">
            <table id="bundles-table" class="table table-sm table-hover mb-0" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>სახელი</th>
                        <th>პროდუქტები</th>
                        <th style="width:90px"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="modal-bundle" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0" id="bundle-modal-title">ახალი კომპლექტი</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-bundle" method="post">
                @csrf
                <input type="hidden" id="bundle_id_field" name="id">
                <div class="modal-body">
                    <label class="form-label small mb-1">სახელი</label>
                    <input type="text" class="form-control form-control-sm" id="bundle_name" name="name"
                           required placeholder="მაგ: შორტი + მაისური კომპლექტი">
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-sm btn-primary">შენახვა</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script>
var bundleTable;

function openCreateModal() {
    $('#bundle-modal-title').text('ახალი კომპლექტი');
    $('#bundle_id_field').val('');
    $('#bundle_name').val('');
    new bootstrap.Modal(document.getElementById('modal-bundle')).show();
}

function toggleBunDrawer() {
    var panel = $('#bun-filter-drawer');
    var isOpen = panel.hasClass('open');
    panel.toggleClass('open', !isOpen);
    $('#bun-filter-btn').toggleClass('active', !isOpen);
}

function buildMobileBunCards() {
    if (window.innerWidth > 767) return;
    $('#bundles-table tbody tr').each(function() {
        var tr = $(this);
        if (tr.find('.bun-mob-cell').length) return;
        var data = bundleTable.row(this).data();
        if (!data) return;
        var name         = data.name         || '';
        var productsHtml = data.products_list || '';
        var actionHtml   = data.action        || '';
        var card = '<div class="bun-mob-card">'
            + '<div class="bun-mob-hdr">'
            +   '<span class="bun-mob-name">' + $('<div>').text(name).html() + '</span>'
            +   '<div class="bun-mob-act">' + actionHtml + '</div>'
            + '</div>'
            + (productsHtml ? '<div class="bun-mob-products">' + productsHtml + '</div>' : '')
            + '</div>';
        tr.append('<td class="bun-mob-cell" colspan="4">' + card + '</td>');
    });
}

$(function() {
    bundleTable = $('#bundles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("api.productBundles") }}',
        columns: [
            { data: 'id',            name: 'id',            width: '50px' },
            { data: 'name',          name: 'name' },
            { data: 'products_list', name: 'products_list', searchable: false, orderable: false },
            { data: 'action',        name: 'action', orderable: false, searchable: false, className: 'text-end' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ka.json' },
        dom: 'rt<"d-flex justify-content-between align-items-center mt-2"ip>',
        pageLength: 25,
        drawCallback: function() { buildMobileBunCards(); }
    });

    /* ── Rebuild cards on desktop→mobile switch ── */
    (function() {
        var _mob = window.innerWidth <= 767, _t;
        $(window).on('resize', function() {
            clearTimeout(_t);
            _t = setTimeout(function() {
                var now = window.innerWidth <= 767;
                if (now && !_mob) buildMobileBunCards();
                _mob = now;
            }, 200);
        });
    })();

    /* ── Mobile filter panel: close on outside click ── */
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.bun-mob-search-wrap').length) {
            $('#bun-filter-drawer').removeClass('open');
            $('#bun-filter-btn').removeClass('active');
        }
    });

    /* ── Page length (desktop + mobile synced) ── */
    $('#dt-page-length').on('change', function() {
        bundleTable.page.len(parseInt($(this).val())).draw();
        $('#mob-bun-page-length').val($(this).val());
    });
    $('#mob-bun-page-length').on('change', function() {
        bundleTable.page.len(parseInt($(this).val())).draw();
        $('#dt-page-length').val($(this).val());
    });

    /* ── Mobile search ── */
    (function() {
        var _t;
        $('#mob-bun-search').on('input', function() {
            clearTimeout(_t); var v = $(this).val();
            _t = setTimeout(function() { bundleTable.search(v).draw(); }, 300);
        });
    })();

    $(document).on('click', '.btn-edit', function() {
        $('#bundle-modal-title').text('კომპლექტის რედაქტირება');
        $('#bundle_id_field').val($(this).data('id'));
        $('#bundle_name').val($(this).data('name'));
        new bootstrap.Modal(document.getElementById('modal-bundle')).show();
    });

    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        swal({ title: 'წაშლა?', icon: 'warning', buttons: ['გაუქმება', 'წაშლა'], dangerMode: true })
            .then(function(ok) {
                if (!ok) return;
                $.ajax({
                    url: '/product-bundles/' + id,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function() { bundleTable.ajax.reload(); },
                    error: function(xhr) { swal('შეცდომა', xhr.responseJSON?.message || '', 'error'); }
                });
            });
    });

    $('#form-bundle').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('[type=submit]');
        if ($submitBtn.prop('disabled')) return;
        $submitBtn.prop('disabled', true).css('opacity', '0.65');
        var id   = $('#bundle_id_field').val();
        var url  = id ? '/product-bundles/' + id : '/product-bundles';
        var data = { _token: '{{ csrf_token() }}', name: $('#bundle_name').val() };
        if (id) data._method = 'PATCH';

        $.ajax({
            url: url, type: 'POST', data: data,
            success: function() {
                bootstrap.Modal.getInstance(document.getElementById('modal-bundle')).hide();
                bundleTable.ajax.reload();
            },
            error: function(xhr) {
                swal('შეცდომა', xhr.responseJSON?.errors?.name?.[0] || xhr.responseJSON?.message || '', 'error');
            },
            complete: function() { $submitBtn.prop('disabled', false).css('opacity', ''); }
        });
    });
});
</script>
@endsection
