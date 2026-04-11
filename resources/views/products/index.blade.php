@extends('layouts.master')

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
/* Responsive expand row */
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
    background-color: #0d6efd;
    border-radius: 50%;
}
</style>
@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-header py-3">
        <div class="row align-items-center g-2">
            <div class="col-12 col-sm-auto">
                <h5 class="mb-0 fw-bold">
                    <i class="fa fa-cubes me-2 text-primary"></i>Products
                </h5>
            </div>
            @if(Auth::user()->role === 'admin')
            <div class="col-12 col-sm-auto ms-sm-auto d-flex align-items-center gap-2 flex-wrap">
                <button onclick="addForm()" class="btn btn-success btn-sm">
                    <i class="fa fa-plus me-1"></i> Add Product
                </button>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Deleted</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="toggle-deleted" role="switch">
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="card-body p-0 p-md-3">
        <div class="table-responsive">
            <table id="products-table" class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:50px;">ID</th>
                        <th class="text-center" style="width:70px;">Image</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th class="text-end">Price</th>
                        <th>Sizes</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('products.form')
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
var save_method;
var table;

$(function() {
    table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('api.products') }}",
        columns: [
            { data: 'id',           className: 'text-center',                    responsivePriority: 6 },
            { data: 'show_photo',   orderable: false, searchable: false, className: 'text-center', responsivePriority: 4 },
            { data: 'name',                                                       responsivePriority: 1 }, // ყოველთვის ჩანს
            { data: 'product_code',                                               responsivePriority: 3 },
            { data: 'category_name',orderable: false, searchable: false,          responsivePriority: 5 },
            { data: 'price_geo',    className: 'text-end',                        responsivePriority: 2 }, // ყოველთვის ჩანს
            { data: 'format_sizes', orderable: false, searchable: false,          responsivePriority: 7 },
            { data: 'status_stock', orderable: false, searchable: false, className: 'text-center', responsivePriority: 3 },
            { data: 'action',       orderable: false, searchable: false, className: 'text-center', responsivePriority: 1 } // ყოველთვის ჩანს
        ],
        language: {
            processing:      '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            search:          '',
            searchPlaceholder: 'Search...',
            lengthMenu:      '_MENU_ per page',
            info:            '_START_–_END_ of _TOTAL_',
            paginate:        { previous: '‹', next: '›' }
        },
        dom: '<"row align-items-center mb-3"<"col-12 col-sm-6"l><"col-12 col-sm-6"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6"i><"col-12 col-sm-6 d-flex justify-content-sm-end"p>>',
        pageLength: 25,
    });

    $('#toggle-deleted').on('change', function() {
        var url = $(this).is(':checked')
            ? "{{ route('api.deleted-products') }}"
            : "{{ route('api.products') }}";
        table.ajax.url(url).load();
    });
});

// ── ADD ──────────────────────────────────────────────────────
function addForm() {
    save_method = 'add';
    $('input[name=_method]').val('POST');
    $('#form-item')[0].reset();
    $('#id').val('');
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

            var currentSizes = data.sizes ? data.sizes.split(',').map(s => s.trim()) : [];
            filterSizes(currentSizes);

            if (data.image) {
                $('#image-preview').html('<img src="' + data.image + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">');
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
                success: function(data) { table.ajax.reload(); swal({ title: 'Deleted!', icon: 'success', timer: 1500 }); },
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

// ── LIGHTBOX ─────────────────────────────────────────────────
$(document).on('click', '.img-thumb', function() {
    document.getElementById('img-lightbox-img').src = $(this).data('src') || this.src;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('img-lightbox')).show();
});
</script>
@endsection