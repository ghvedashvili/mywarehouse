@extends('layouts.master')


@section('top')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@endsection

@section('content')
    <div class="box box-success">

        <div class="box-header">
            <h3 class="box-title">List of Products</h3>

            <a onclick="addForm()" class="btn btn-success pull-right" style="margin-top: -8px;"><i class="fa fa-plus"></i> Add Products</a>
        </div>


        <!-- /.box-header -->
        <div class="box-body">
            <table id="products-table" class="table table-bordered table-hover table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
 
                    <th>Price_geo</th>
                    <!-- <th>Qty.</th> -->
 
                    <th>Price_usa</th>
                    <!-- <th>Qty.</th>
    -->
                    <th>Image</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <!-- /.box-body -->
    </div>

    @include('products.form')

@endsection

@section('bot')

    <!-- DataTables -->
    <script src=" {{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }} "></script>
    <script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }} "></script>

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
    // DataTable-ის ინიციალიზაცია
    var table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('api.products') }}",
        columns: [
            {data: 'id', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'price_geo', name: 'price_geo'},
            {data: 'price_usa', name: 'price_usa'},
            // {data: 'qty', name: 'qty'},
            {data: 'show_photo', name: 'show_photo', orderable: false, searchable: false},
            {data: 'category_name', name: 'category_name'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    function addForm() {
        save_method = "add";
        $('input[name=_method]').val('POST');
        $('#modal-form').modal('show');
        $('#modal-form form')[0].reset();
        $('.modal-title').text('Add Products');
        $('#image-preview').empty(); 
    }

    function editForm(id) {
        save_method = 'edit';
        $('input[name=_method]').val('PATCH');
        $('#modal-form form')[0].reset();
        $('#image-preview').empty();

        $.ajax({
            url: "{{ url('products') }}" + '/' + id + "/edit",
            type: "GET",
            dataType: "JSON",
            success: function(data) {
    $('#modal-form').modal('show');
    $('.modal-title').text('Edit Products');

    $('#id').val(data.id);
    $('#name').val(data.name);
    
    // მნიშვნელოვანია: დავრწმუნდეთ, რომ პატარა/დიდი ასოები ემთხვევა HTML-ს
    $('#price_geo').val(data.price_geo);
    $('#price_usa').val(data.price_usa); 
    // $('#qty').val(data.qty);
    $('#category_id').val(data.category_id);

    // სურათის ჩვენების ლოგიკა
    $('#image-preview').empty(); // ჯერ ვასუფთავებთ
    
    // ვამოწმებთ 'image' ველს (ან 'show_photo', გააჩნია რას აბრუნებს კონტროლერი)
    var imagePath = data.image || data.show_photo; 

    if (imagePath) {
        // თუ ბაზაში ინახება სრული path (მაგ: uploads/product/img.jpg)
        // მაშინ url('') + imagePath სწორია
        var imageUrl = "{{ url('') }}/" + imagePath.replace(/^\//, ''); 
        
        $('#image-preview').html(
            '<p>Current Image:</p>' +
            '<img src="' + imageUrl + '" class="img-thumbnail" style="width:150px; height:150px; object-fit:cover;">'
        );
    }
},success: function(data) {
    $('#modal-form').modal('show');
    $('.modal-title').text('Edit Products');

    $('#id').val(data.id);
    $('#name').val(data.name);
    
    // კონტროლერიდან მოდის პატარა ასოებით: price_geo, price_usa
    // ფორმაში კი ID-ები გაქვს: price_geo, price_usa
    $('#price_geo').val(data.price_geo);
    $('#price_usa').val(data.price_usa); 
    // $('#qty').val(data.qty);
    $('#category_id').val(data.category_id);

    $('#image-preview').empty();
    
    if (data.image) {
        // რადგან კონტროლერში გზას ინახავ როგორც /upload/..., 
        // აქ უბრალოდ url('') + data.image საკმარისია
        var imageUrl = "{{ url('') }}" + data.image;
        
        $('#image-preview').html(
            '<p style="margin-top:10px;">Current Image:</p>' +
            '<img src="' + imageUrl + '" class="img-thumbnail" style="width:120px; height:120px; object-fit:cover;">'
        );
    }
},
            error: function() {
                swal("Error", "Could not fetch data", "error");
            }
        });
    }

    function deleteData(id) {
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
                url: "{{ url('products') }}" + '/' + id,
                type: "POST",
                data: {'_method': 'DELETE', '_token': csrf_token},
                success: function(data) {
                    table.ajax.reload();
                    swal("Success!", data.message, "success");
                },
                error: function() {
                    swal("Oops...", "Something went wrong!", "error");
                }
            });
        });
    }

    $(function() {
        $('#modal-form form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                var id = $('#id').val();
                var url = (save_method == 'add') ? "{{ url('products') }}" : "{{ url('products') }}/" + id;

                $.ajax({
                    url: url,
                    type: "POST",
                    data: new FormData($("#modal-form form")[0]),
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        $('#modal-form').modal('hide');
                        table.ajax.reload();
                        swal("Success!", data.message, "success");
                    },
                    error: function(data) {
                        swal("Error", "Could not save data!", "error");
                    }
                });
                return false;
            }
        });
    });
</script>

@endsection
