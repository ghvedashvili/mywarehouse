@extends('layouts.master')

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

<div class="container-fluid py-3 app-container">
    <div class="card border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-users me-2 text-primary"></i> კლიენტების მართვა</h6>
            <div class="d-flex gap-2">
                <button onclick="addForm()" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa fa-plus me-1"></i> დამატება
                </button>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm border rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa fa-download me-1"></i> ექსპორტი
                    </button>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item" href="{{ route('exportPDF.customersAll') }}"><i class="fa fa-file-pdf text-danger me-2"></i> PDF</a></li>
                        <li><a class="dropdown-item" href="{{ route('exportExcel.customersAll') }}"><i class="fa fa-file-excel text-success me-2"></i> Excel</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold text-muted small">გაფილტვრა ქალაქით</label>
                    <select id="filter_city" class="form-select form-select-sm border-0 bg-light">
                        <option value="">ყველა ქალაქი</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="customer-table" class="table table-hover align-middle w-100">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">ID</th>
                            <th class="border-0">სახელი</th>
                            <th class="border-0">ქალაქი</th>
                            <th class="border-0">მისამართი</th>
                            <th class="border-0">Email</th>
                            <th class="border-0">კონტაქტი</th>
                            <th class="border-0">შენიშვნა</th>
                            <th class="border-0 text-center">მოქმედება</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('customers.form')
@endsection

@section('bot')
<script>
var table = $('#customer-table').DataTable({
    processing: true,
    serverSide: true,
    paging: true,
    pageLength: 10,
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
        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/ka.json"
    },
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    renderer: 'bootstrap'
});

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