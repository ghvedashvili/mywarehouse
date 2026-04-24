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
            @if(Auth::user()->role === 'admin')
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i> ახალი
            </button>
            @endif
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
    $('#modal-form form')[0].reset();
    $.ajax({
        url: "{{ url('brands') }}/" + id + "/edit",
        type: "GET", dataType: "JSON",
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
                }
            });
            return false;
        }
    });
});
</script>
@endsection
