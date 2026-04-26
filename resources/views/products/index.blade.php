@extends('layouts.master')
@section('page_title')<i class="fa fa-cubes me-2" style="color:#3498db;"></i>პროდუქტები@endsection

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
/* Select2 */
.select2-container--default .select2-selection--single { height: 38px; border: 1px solid #dee2e6; border-radius: 6px; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; padding-left: 10px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
/* Toggle */
.form-switch .form-check-input { width: 2.5em; height: 1.3em; cursor: pointer; }
/* Image thumb */
.img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; cursor: zoom-in; transition: transform 0.15s; border: 1px solid #dee2e6; }
.img-thumb:hover { transform: scale(1.1); }
/* Table */
#products-table td { vertical-align: middle; }
#products-table td:nth-child(5) { padding: 4px 3px; cursor: zoom-in; }
/* Responsive — hide "+" icon, expand on name click */
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before { display: none !important; }
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control { cursor: pointer; padding-left: 8px !important; }
#products-table tbody tr td:nth-child(1) { cursor: pointer; }
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
            @if(Auth::user()->role === 'admin')
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი</span>
            </button>
            @endif
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
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script>
var save_method;
var table;

$(function() {
    table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: { details: { type: 'column', target: 0 } },
        autoWidth: false,
        order: [],
        ajax: "{{ route('api.products') }}",
        columns: [
            { data: 'name',                                                                                          responsivePriority: 1 },
            { data: 'product_code',                                                width: '90px',                   responsivePriority: 5 },
            { data: 'category_name', orderable: false, searchable: false,          width: '110px',                  responsivePriority: 6 },
            { data: 'brand_name',    orderable: false, searchable: false,          width: '100px',                  responsivePriority: 7 },
            { data: 'bundle_name',   orderable: false, searchable: false,          width: '130px',                  responsivePriority: 8 },
            { data: 'price_geo',     className: 'text-end',                        width: '70px',                   responsivePriority: 2 },
            { data: 'show_photo',    orderable: false, searchable: false, className: 'text-center', width: '56px',  responsivePriority: 4 },
            { data: 'format_sizes',  orderable: false, searchable: false,          width: '90px',                   responsivePriority: 8 },
            { data: 'status_stock',  orderable: false, searchable: false, className: 'text-center', width: '62px',  responsivePriority: 3 },
            { data: 'action',        orderable: false, searchable: false, className: 'text-center', width: '70px',  responsivePriority: 1 }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            info:       '_START_–_END_ of _TOTAL_',
            paginate:   { previous: '‹', next: '›' }
        },
        dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
        pageLength: 25,
    });

    $('#toggle-deleted').on('change', function() {
        var url = $(this).is(':checked')
            ? "{{ route('api.deleted-products') }}"
            : "{{ route('api.products') }}";
        table.ajax.url(url).load();
    });

    $('#dt-page-length').on('change', function() {
        table.page.len(parseInt($(this).val())).draw();
    });

    $('#dt-search').on('input', function() {
        table.search($(this).val()).draw();
    });
});

// ── SELECT2 for brand_id (with logo) ─────────────────────────
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

// ── ADD ──────────────────────────────────────────────────────
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

// ── EDIT ─────────────────────────────────────────────────────
function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#form-item')[0].reset();

    $.ajax({
        url: "{{ url('products') }}/" + id + "/edit",
        type: "GET", dataType: "JSON",
        success: function(data) {
            $('.modal-title').text('Edit Product');
            $('#id').val(data.id);
            $('#name').val(data.name);
            $('#product_code').val(data.product_code);
            $('#price_geo').val(data.price_geo || data.Price_geo);
            $('#product_status').prop('checked', data.product_status == 1);
            $('#in_warehouse').prop('checked', data.in_warehouse == 1);
            $('#category_id').val(data.category_id);
            $('#brand_id').val(data.brand_id || null).trigger('change');
            $('#bundle_id').val(data.bundle_id || '');

            var currentSizes = data.sizes ? data.sizes.split(',').map(s => s.trim()) : [];
            filterSizes(currentSizes);

            if (data.image_url) {
                $('#image-preview').html('<img src="' + data.image_url + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">');
            } else {
                $('#image-preview').html('<span class="text-muted">No Preview</span>');
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
        },
        error: function() {
            swal({ title: 'Error', text: 'Could not fetch data', icon: 'error' });
        }
    });
}

// ── SIZES ────────────────────────────────────────────────────
function filterSizes(selectedSizes) {
    selectedSizes = selectedSizes || [];
    var catId = $('#category_id').val();
    if (!catId) {
        $('#size-checkboxes').html('<span class="text-muted" style="font-size:12px;">Choose a category first</span>');
        return;
    }
    $.get("{{ url('get-sizes') }}/" + catId, function(data) {
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
    });
}

// ── SAVE ─────────────────────────────────────────────────────
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
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i>Save Changes');
            }
        });
    });
});

// ── DELETE ───────────────────────────────────────────────────
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

// ── RESTORE ──────────────────────────────────────────────────
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

// ── ACTION BUTTONS → stop row expand ─────────────────────────
$('#products-table').on('click', 'a.btn', function(e) {
    e.stopPropagation();
});

// ── STATUS MODAL ─────────────────────────────────────────────
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

// ── LIGHTBOX ─────────────────────────────────────────────────
$(document).on('click', '.img-thumb', function() {
    document.getElementById('img-lightbox-img').src = $(this).data('src') || this.src;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('img-lightbox')).show();
});
</script>
@endsection