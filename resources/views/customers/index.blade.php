@extends('layouts.master')
@section('page_title')<i class="fa fa-users me-2" style="color:#27ae60;"></i>მომხმარებლები@endsection

@section('content')
<style>
    /* კომპაქტური სტილები */
    .app-container { font-size: 0.85rem !important; }
    .table { font-size: 0.82rem !important; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .form-control-sm, .form-select-sm { font-size: 0.82rem; }
    
    /* DataTables Pagination-ის გასწორება Bootstrap 5-ისთვის */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0 !important;
        margin-left: 2px !important;
        border: none !important;
    }
    .page-link { padding: 0.3rem 0.6rem !important; font-size: 0.8rem !important; }
    
    /* ვალიდაციის სტილი */
    .was-validated .form-control:invalid, .was-validated .form-select:invalid {
        border-color: #dc3545 !important;
    }
    .invalid-feedback { font-size: 0.75rem; }
</style>

<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-users me-2" style="color:#27ae60;"></i>მომხმარებლები</h2>
            <p class="mod-subtitle">კლიენტების მართვა</p>
        </div>
        <div class="mod-actions">
            <button onclick="addForm()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">დამატება</span>
            </button>
            <a href="{{ route('exportPDF.customersAll') }}" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca;">
                <i class="fa fa-file-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
            </a>
            <a href="{{ route('exportExcel.customersAll') }}" class="btn btn-sm" style="background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;">
                <i class="fa fa-file-excel me-1"></i><span class="d-none d-sm-inline">Excel</span>
            </a>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-toolbar">
            <select id="dt-page-length" class="form-select form-select-sm" style="width:75px;">
                <option value="10">10</option>
                <option value="25">25</option>
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

</div>{{-- /mod-wrap --}}

@include('customers.form')
@endsection

@section('bot')
<script>
var table = $('#customer-table').DataTable({
    processing: true,
    serverSide: true,
    paging: true,
    pageLength: 25,
    ajax: {
        url: "{{ route('api.customers') }}",
        data: function(d) { d.city_id = $('#filter_city').val(); }
    },
    columns: [
        {data:'id'}, {data:'name'}, {data:'city_name'}, {data:'address'},
        {data:'email'}, {data:'contact_info'}, {data:'comment'},
        {data:'action', orderable:false, searchable:false}
    ],
    language: { search: '', searchPlaceholder: 'ძებნა...' },
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
});

$('#dt-search').on('keyup', function() { table.search(this.value).draw(); });
$('#dt-page-length').on('change', function() { table.page.len(this.value).draw(); });

$('#filter_city').change(function() { table.draw(); });

function addForm() {
    save_method = "add";
    $('input[name=_method]').val('POST');
    $('#modal-form form')[0].reset();
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
                success: function(data) {
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
        
        var id  = $('#id').val();
        var url = (save_method == 'add') ? "{{ url('customers') }}" : "{{ url('customers') }}/" + id;
        
        $.ajax({
            url: url,
            type: "POST",
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(data) {
                bootstrap.Modal.getInstance(document.getElementById('modal-form')).hide();
                table.ajax.reload();
                Swal.fire({ title: 'წარმატება!', text: data.message, icon: 'success', timer: 1500 });
            },
            error: function(data) {
    if (data.status === 422) { // ვალიდაციის შეცდომა
        var errors = data.responseJSON.errors;
        var errorMessages = "";

        // ყველა შეცდომის შეგროვება ერთ ტექსტში
        $.each(errors, function(key, value) {
            errorMessages += value[0] + "<br>"; 
        });

        Swal.fire({
            title: 'ვალიდაციის შეცდომა!',
            html: errorMessages, // აქ გამოჩნდება "ტელეფონი უკვე გამოყენებულია" და ა.შ.
            icon: 'error'
        });
    } else {
        Swal.fire({
            title: 'Oops!',
            text: 'დაფიქსირდა გაუთვალისწინებელი შეცდომა',
            icon: 'error'
        });
    }
}
        });
        return false;
    });
});
</script>
@endsection