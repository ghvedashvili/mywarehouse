@extends('layouts.master')


@section('top')
    <!-- Log on to codeastro.com for more projects! -->
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
<style>
.switch { position: relative; display: inline-block; width: 46px; height: 24px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
.switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }
</style>
    @endsection

@section('content')


        <div class="box-header">
            <h3 class="box-title">List of Categories</h3>
        </div>

        <div class="box-header">
    @if(Auth::user()->role === 'admin')
        <a onclick="addForm()" class="btn btn-success"><i class="fa fa-plus"></i> Add New Category</a>

        <div class="switch-wrapper" style="display:inline-flex; align-items:center; gap:8px; margin-left:10px; vertical-align:middle;">
            <label for="toggle-deleted" style="font-size:13px; color:#666; margin:0; cursor:pointer;">Deleted</label>
            <label class="switch" style="margin:0;">
                <input type="checkbox" id="toggle-deleted">
                <span class="switch-slider"></span>
            </label>
        </div>
    @endif
    <a href="{{ route('exportPDF.categoriesAll') }}" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
    <a href="{{ route('exportExcel.categoriesAll') }}" class="btn btn-primary"><i class="fa fa-file-excel-o"></i> Export Excel</a>
</div>


        <!-- /.box-header -->
        <div class="box-body">
            <table id="categories-table" class="table table-bordered table-hover table-striped">
    <thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Sizes</th>
    <th>Courier Price</th>
    <th>Status</th>
    
    <th>Actions</th>
</tr>
</thead>
    <tbody></tbody>
</table>
        </div>
        <!-- /.box-body -->
    </div><!-- Log on to codeastro.com for more projects! -->

    @include('categories.form')

@endsection

@section('bot')

    <!-- DataTables -->
   

    {{-- Validator --}}
    <script src="{{ asset('assets/validator/validator.min.js') }}"></script>

    {{--<script>--}}
    {{--$(function () {--}}
    {{--$('#items-table').DataTable()--}}
    {{--$('#example2').DataTable({--}}
    {{--'paging'      : true,--}}
    {{--'lengthChange': false,--}}
    {{--'searching'   : false,--}}
    {{--'ordering'    : true,--}}
    {{--'info'        : true,--}}
    {{--'autoWidth'   : false--}}
    {{--})--}}
    {{--})--}}
    {{--</script>--}}

   <script type="text/javascript">
    // ინიციალიზაცია ხდება ერთხელ
   var showingDeleted = false;

var table = $('#categories-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('api.categories') }}",
    columns: [
        {data: 'id',             name: 'id'},
        {data: 'name',           name: 'name'},
        {data: 'sizes_display',  name: 'sizes_display',  orderable: false, searchable: false},
        {data: 'international_courier_price', name: 'international_courier_price'},
        {data: 'status_display', name: 'status_display', orderable: false, searchable: false, visible: false},
        
        {data: 'action',         name: 'action',         orderable: false, searchable: false}
    ]
});

$(function() {
    $('#toggle-deleted').on('change', function() {
        showingDeleted = $(this).is(':checked');
        if (showingDeleted) {
            table.column(3).visible(true);
            table.ajax.url("{{ route('api.categories.deleted') }}").load();
        } else {
            table.column(3).visible(false);
            table.ajax.url("{{ route('api.categories') }}").load();
        }
    });
});

function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({
        title: 'Restore Category?',
        text: "კატეგორია დაბრუნდება active სტატუსში",
        icon: 'warning',
        buttons: true,
    }).then((willRestore) => {
        if (willRestore) {
            $.ajax({
                url: "{{ url('categories') }}/" + id + "/restore",
                type: "POST",
                data: {'_method': 'PATCH', '_token': csrf_token},
                success: function(data) {
                    table.ajax.reload();
                    swal("Restored!", data.message, "success");
                },
                error: function() {
                    swal("Error", "აღდგენისას დაფიქსირდა შეცდომა", "error");
                }
            });
        }
    });
}

    function addForm() {
        save_method = "add";
        $('input[name=_method]').val('POST');
        $('#modal-form').modal('show');
        $('#modal-form form')[0].reset();
        $('.modal-title').text('Add Categories');
    }

    function editForm(id) {
    save_method = 'edit';
    $('input[name=_method]').val('PATCH');
    $('#modal-form form')[0].reset();
    $.ajax({
        url: "{{ url('categories') }}" + '/' + id + "/edit",
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            $('#modal-form').modal('show');
            $('.modal-title').text('Edit Category');

            $('#id').val(data.id);
            $('#name').val(data.name);
            // აი ეს ხაზი დაამატე:
            $('#sizes').val(data.sizes);
            $('#international_courier_price').val(data.international_courier_price);
        },
        error : function() {
            alert("Nothing Data");
        }
    });
}

    function deleteData(id){
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        swal({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(function () {
            $.ajax({
                url : "{{ url('categories') }}" + '/' + id,
                type : "POST",
                data : {'_method' : 'DELETE', '_token' : csrf_token},
                success : function(data) {
                    table.ajax.reload();
                    swal({
                        title: 'Success!',
                        text: data.message,
                        type: 'success',
                        timer: '1500'
                    })
                },
               error : function (data) {
                // აქ ვიჭერთ 403 შეცდომას (ან ნებისმიერ სხვა შეცდომას)
                var response = data.responseJSON;
                
                swal({
                    title: 'შეცდომა!',
                    text: response.message ? response.message : 'რაღაც შეცდომა მოხდა!',
                    type: 'error',
                    timer: '3000'
                });
            }
            });
        });
    }

    $(function(){
        $('#modal-form form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()){
                var id = $('#id').val();
                var url;
                if (save_method == 'add') url = "{{ url('categories') }}";
                else url = "{{ url('categories') . '/' }}" + id;

                $.ajax({
                    url : url,
                    type : "POST",
                    data: new FormData($("#modal-form form")[0]),
                    contentType: false,
                    processData: false,
                    success : function(data) {
                        $('#modal-form').modal('hide');
                        table.ajax.reload();
                        swal({
                            title: 'Success!',
                            text: data.message,
                            type: 'success',
                            timer: '1500'
                        })
                    },
                    error : function(data){
                        var response = JSON.parse(data.responseText);
                        var errorString = "";
                        
                        if (data.status === 422) {
                            $.each(response.errors, function (key, value) {
                                errorString += value + " ";
                            });
                        } else {
                            errorString = "Something went wrong!";
                        }

                        swal({
                            title: 'Oops...',
                            text: errorString,
                            type: 'error',
                            timer: '3000'
                        });
                    }
                });
                return false;
            }
        });
    });
</script>

@endsection
