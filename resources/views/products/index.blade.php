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
            <div class="row" style="margin-bottom: 20px;">
    <div class="col-md-3">
        <label>Filter by Category</label>
        <select id="filter_category" class="form-control">
            <option value="">All Categories</option>
            @foreach($category as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label>Filter by Status</label>
        <select id="filter_status" class="form-control">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Filter by Stock</label>
        <select id="filter_stock" class="form-control">
            <option value="">All Stock</option>
            <option value="1">In Stock</option>
            <option value="0">Out of Stock</option>
        </select>
    </div>
</div>
            <table id="products-table" class="table table-bordered table-hover table-striped">
                <thead>
                <tr>
                    <th>ID</th>
        <th>Code</th>
        <th>Name</th>
        <th>Price geo</th>
        <th>Price usa</th>
        <th>Sizes</th>
        <th>Image</th>
        <th>Status</th>
        <th>Stock</th>
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
    lengthMenu: [
        [10, 25, 50, 100, -1], // რეალური მნიშვნელობები (-1 ნიშნავს ყველას)
        [10, 25, 50, 100, "All"] // რას დაწერს მომხმარებლისთვის დროპდაუნში
    ],
    pageLength: 10, // სტანდარტულად რამდენი გამოჩნდეს ჩატვირთვისას
    ajax: {
        url: "{{ route('api.products') }}",
        data: function (d) {
            d.category_id = $('#filter_category').val();
            d.product_status = $('#filter_status').val();
            d.in_warehouse = $('#filter_stock').val();
        }
    },
    columns: [
        {data: 'id', name: 'id'},
        {data: 'product_code', name: 'product_code'},
        {data: 'name', name: 'name'},
        {data: 'price_geo', name: 'price_geo'},
        {data: 'price_usa', name: 'price_usa'},
        {data: 'format_sizes', name: 'format_sizes', orderable: false},
        {data: 'show_photo', name: 'show_photo', orderable: false, searchable: false},
        {data: 'status_label', name: 'status_label'},
        {data: 'warehouse_label', name: 'warehouse_label'},
        {data: 'category_name', name: 'category_name'},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ]
});

// ფილტრების შეცვლისას ცხრილის ავტომატური განახლება
$('#filter_category, #filter_status, #filter_stock').change(function(){
    table.draw();
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
            $('.modal-title').text('Edit Product');

            // ძირითადი ინპუტების შევსება
            $('#id').val(data.id);
            $('#product_code').val(data.product_code);
            $('#name').val(data.name);
            $('#price_geo').val(data.price_geo);
            $('#price_usa').val(data.price_usa);
            $('#category_id').val(data.category_id);

            // სტატუსების მონიშვნა (Prop მეთოდი საუკეთესოა ჩეკბოქსებისთვის)
            $('#product_status').prop('checked', data.product_status == 1);
            $('#in_warehouse').prop('checked', data.in_warehouse == 1);

            // ზომების დამუშავება
            // ბაზიდან მოდის სტრიქონი "S,M", ვაქცევთ მასივად ["S", "M"]
            var currentSizes = [];
            if (data.sizes) {
                currentSizes = data.sizes.split(',').map(function(item) {
                    return item.trim();
                });
            }
            
            // ვიძახებთ ზომების ფილტრაციას და გადავცემთ არჩეულ ზომებს მოსანიშნად
            filterSizes(currentSizes);

            // სურათის ჩვენების ლოგიკა
            if (data.image) {
                // რადგან ბაზაში გზა იწყება /upload-ით, url('') პირდაპირ დაემატება
                var imageUrl = "{{ url('') }}" + data.image; 
                
                $('#image-preview').html(
                    '<p style="margin-top:10px; font-weight: bold;">Current Image:</p>' +
                    '<img src="' + imageUrl + '" class="img-thumbnail" style="width:120px; height:120px; object-fit:cover;">'
                );
            }
        },
        error: function() {
            swal({
                title: 'Error',
                text: 'მონაცემების წამოღება ვერ მოხერხდა!',
                type: 'error',
                timer: '1500'
            });
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

    function filterSizes(selectedSizes = []) {
    var categoryId = $('#category_id').val();
    var container = $('#size-checkboxes');
    var group = $('#sizes-group');

    container.empty(); // ჯერ ვასუფთავებთ კონტეინერს

    if (!categoryId) {
        group.hide();
        return;
    }

    $.ajax({
        url: "{{ url('get-sizes') }}/" + categoryId,
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            if (data.length > 0) {
                data.forEach(function(size) {
                    // ვამოწმებთ არის თუ არა ეს ზომა არჩეულების მასივში
                    // ვიყენებთ trim()-ს იმ შემთხვევისთვის თუ ბაზაში ზედმეტი დაშორებებია
                    var isChecked = selectedSizes.includes(size.name.trim()) ? 'checked' : '';
                    
                    var checkbox = `
                        <label style="font-weight: normal; margin-right: 15px; cursor: pointer;">
                            <input type="checkbox" name="sizes[]" value="${size.name}" ${isChecked} class="size-checkbox"> 
                            ${size.name}
                        </label>`;
                    container.append(checkbox);
                });
                group.show();
            } else {
                group.hide();
            }
        },
        error: function() {
            console.error("ზომების წამოღება ვერ მოხერხდა");
        }
    });
}

$(document).ready(function() {
    // 1. პროდუქტის არჩევისას ფასების შევსება
    $('#product_id').on('change', function() {
        var productId = $(this).val();
        if (productId) {
            $.ajax({
                url: "{{ url('products') }}/" + productId + "/edit", // ვიყენებთ არსებულ edit მეთოდს
                type: "GET",
                dataType: "JSON",
                success: function(data) {
                    // ვავსებთ ფასებს (დარწმუნდით, რომ ბაზაში ამ სვეტებს ასე ჰქვია)
                    $('#price_usa').val(data.price_usa || 0);
                    $('#price_georgia').val(data.price_georgia || 0);
                    calculateBalance(); // გადავთვალოთ ბალანსი
                }
            });
        }
    });

    // 2. ბალანსის დათვლის ფუნქცია
    function calculateBalance() {
        var totalPrice = parseFloat($('#price_georgia').val()) || 0;
        
        var tbc = parseFloat($('#paid_tbc').val()) || 0;
        var bog = parseFloat($('#paid_bog').val()) || 0;
        var lib = parseFloat($('#paid_lib').val()) || 0;
        var cash = parseFloat($('#paid_cash').val()) || 0;

        var paidTotal = tbc + bog + lib + cash;
        var balance = totalPrice - paidTotal;

        // გამოჩენა და ფერის შეცვლა
        var balanceDisplay = $('#balance_display');
        balanceDisplay.text(balance.toFixed(2));

        if (balance > 0) {
            balanceDisplay.css('color', 'red'); // თუ დასამატებელია თანხა
        } else if (balance < 0) {
            balanceDisplay.css('color', 'blue'); // თუ ზედმეტია გადახდილი
        } else {
            balanceDisplay.css('color', 'green'); // თუ ნულია
        }
    }

    // მოვუსმინოთ ყველა ციფრული ველის ცვლილებას
    $('#price_georgia, #paid_tbc, #paid_bog, #paid_lib, #paid_cash').on('input', function() {
        calculateBalance();
    });
});
</script>

@endsection
