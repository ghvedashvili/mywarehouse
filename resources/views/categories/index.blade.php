@extends('layouts.master')
@section('page_title')<i class="fa fa-tags me-2" style="color:#f39c12;"></i>კატეგორიები@endsection

@section('top')
<style>
.switch { position: relative; display: inline-block; width: 46px; height: 24px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
.switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }

/* Categories table layout */
#categories-table { table-layout: fixed; width: 100% !important; word-wrap: break-word; }
#categories-table td { white-space: normal !important; word-break: break-word; vertical-align: middle; }
#categories-table td:last-child { white-space: nowrap !important; }
#categories-table .label { display: inline-block; margin-bottom: 2px; }
.table-responsive { overflow-x: hidden; }
</style>
@endsection

@section('content')
<div class="p-2 p-md-3">
<div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <select id="dt-page-length" class="form-select form-select-sm" style="width:auto; flex-shrink:0;">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1">ყველა</option>
        </select>
        <input id="dt-search" type="search" class="form-control form-control-sm" placeholder="ძებნა..." style="flex:1 1 120px; min-width:80px;">
        @if(Auth::user()->role === 'admin')
        <button onclick="addForm()" class="btn btn-success btn-sm" style="flex-shrink:0;">
            <i class="fa fa-plus"></i><span class="d-none d-md-inline"> Add New</span>
        </button>
        <div class="d-flex align-items-center gap-2" style="flex-shrink:0;">
            <label for="toggle-deleted" class="mb-0 text-muted" style="font-size:13px; cursor:pointer;">Deleted</label>
            <label class="switch mb-0">
                <input type="checkbox" id="toggle-deleted">
                <span class="switch-slider"></span>
            </label>
        </div>
        @endif
        <a href="{{ route('exportPDF.categoriesAll') }}" class="btn btn-danger btn-sm" style="flex-shrink:0;">
            <i class="fa fa-file-pdf"></i><span class="d-none d-md-inline"> PDF</span>
        </a>
        <a href="{{ route('exportExcel.categoriesAll') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
            <i class="fa fa-file-excel"></i><span class="d-none d-md-inline"> Excel</span>
        </a>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="table-responsive">
        <table id="categories-table" class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>Name</th>
                    <th>Sizes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>{{-- /table-responsive --}}
    </div>
</div>
</div>{{-- /p-2 p-md-3 --}}

@include('categories.form')
@endsection

@section('bot')
<script src="{{ asset('assets/validator/validator.min.js') }}"></script>
<script>
// Bootstrap tooltips
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
       // {data: 'id',             name: 'id'},
        {data: 'name',           name: 'name',           width: '30%'},
        {data: 'sizes_display',  name: 'sizes_display',  orderable: false, searchable: false, width: '45%'},
        {data: 'status_display', name: 'status_display', orderable: false, searchable: false, visible: false, width: '60px'},
        {data: 'action',         name: 'action',         orderable: false, searchable: false, width: '70px'}
    ]
});

$('#dt-page-length').on('change', function() {
    table.page.len(parseInt($(this).val())).draw();
});

$('#dt-search').on('input', function() {
    table.search($(this).val()).draw();
});

$('#toggle-deleted').on('change', function() {
    showingDeleted = $(this).is(':checked');
    if (showingDeleted) {
        table.column(2).visible(true);
        table.ajax.url("{{ route('api.categories.deleted') }}").load();
    } else {
        table.column(2).visible(false);
        table.ajax.url("{{ route('api.categories') }}").load();
    }
});

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
        type: "GET", dataType: "JSON",
        success: function(data) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
            $('.modal-title').text('Edit Category');
            $('#id').val(data.id);
            $('#name').val(data.name);
            $('#sizes').val(data.sizes);
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
                }
            });
            return false;
        }
    });
});
</script>
@endsection