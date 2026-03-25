@extends('layouts.master')


@section('top')
    <!-- DataTables -->
     
<style>
.switch-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: -2px;
}
.switch-wrapper label {
    font-size: 13px;
    color: #666;
    margin: 0;
    cursor: pointer;
}
.switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
    margin: 0;
}
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    border-radius: 24px;
    transition: .3s;
}
.switch-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: .3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.switch input:checked + .switch-slider { background-color: #e74c3c; }
.switch input:checked + .switch-slider:before { transform: translateX(22px); }
</style>

    @endsection

@section('content')
    <div class="box box-success">
 @if(auth()->user()->role == 'admin')

        <div class="box-header">
    <h3 class="box-title">List of Products</h3>

    <a onclick="addForm()" class="btn btn-success pull-right" style="margin-top:-8px;">
        <i class="fa fa-plus"></i> Add Products
    </a>

    <div class="pull-right switch-wrapper" style="margin-right:10px;">
    <label for="toggle-deleted">Deleted</label>
    <label class="switch">
        <input type="checkbox" id="toggle-deleted">
        <span class="switch-slider"></span>
    </label>
</div>
</div>

@endif

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
    <th>Code</th>
    <th>Image</th>
    <th>Name</th>
    <th>Category</th>
    <th>Sizes</th>
    <th>Price GEO</th>
   @if(auth()->user()->role == 'admin')
<th>Price USA</th>
@endif
    <th>Status / Stock</th>
    @if(auth()->user()->role == 'admin')
<th>Actions</th>
@endif
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
   var isAdmin = {{ auth()->user()->role == 'admin' ? 'true' : 'false' }};

var columns = [
    {data: 'product_code', name: 'product_code'},
    {data: 'show_photo', name: 'show_photo', orderable: false, searchable: false},
    {data: 'name', name: 'name'},
    {data: 'category_name', name: 'category_name'},
    {data: 'format_sizes', name: 'format_sizes', orderable: false},
    {data: 'price_geo', name: 'price_geo'},
];

if (isAdmin) {
    columns.push({data: 'price_usa', name: 'price_usa'});
}

columns.push({data: 'status_stock', name: 'status_stock', orderable: false, searchable: false});

if (isAdmin) {
    columns.push({data: 'action', name: 'action', orderable: false, searchable: false});
}

var table = $('#products-table').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    pageLength: 10,
    ajax: {
        url: "{{ route('api.products') }}", // active products პირველი
        data: function (d) {
            d.category_id = $('#filter_category').val();
            d.product_status = $('#filter_status').val();
            d.in_warehouse = $('#filter_stock').val();
            d.is_admin = isAdmin ? 1 : 0;
        }
    },
    columns: columns
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
    $('#image-preview').html('<span class="text-muted">No Preview</span>');
    
    // sizes კონტეინერის გასუფთავება
    $('#size-checkboxes').empty().append(
        '<span class="text-muted" style="font-size:12px; color:#aaa;">Choose a category first</span>'
    );
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
    '<img src="' + imageUrl + '" ' +
    'class="img-thumbnail img-zoom-trigger" ' +
    'style="width:100%; height:100%; object-fit:cover; cursor:pointer; display:block;">'
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

   {{-- ეს ჩაანაცვლე index.blade.php-ში არსებული filterSizes() ფუნქცია --}}
function filterSizes(selectedSizes = []) {
    var categoryId = $('#category_id').val();
    var container = $('#size-checkboxes');

    // ვასუფთავებთ
    container.empty();

    if (!categoryId) {
        container.append('<span class="text-muted" style="font-size:12px; color:#aaa;">Choose a category first</span>');
        return;
    }

    $.ajax({
        url: "{{ url('get-sizes') }}/" + categoryId,
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            if (data.length > 0) {
                data.forEach(function(size) {
                    var isChecked = selectedSizes.includes(size.name.trim()) ? 'checked' : '';
                    var checkbox = `
                        <label style="font-weight:normal; margin:0; cursor:pointer; font-size:13px; white-space:nowrap;">
                            <input type="checkbox" name="sizes[]" value="${size.name}" ${isChecked} class="size-checkbox">
                            ${size.name}
                        </label>`;
                    container.append(checkbox);
                });
            } else {
                container.append('<span class="text-muted" style="font-size:12px; color:#aaa;">No sizes for this category</span>');
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


$(document).on('click', '.img-zoom-trigger', function() {
    $('#img-lightbox-img').attr('src', $(this).attr('src'));
    $('#img-lightbox').modal('show');
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape') $('#img-lightbox').modal('hide');
});

var showingDeleted = false; // იტვირთება deleted-ით დასაწყისში

$(function() {
    $('#toggle-deleted').on('change', function() {
        showingDeleted = $(this).is(':checked');
        if (showingDeleted) {
            table.ajax.url("{{ route('api.deleted-products') }}").load();
        } else {
            table.ajax.url("{{ route('api.products') }}").load();
        }
    });
});

function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    swal({
        title: 'Restore Product?',
        text: 'პროდუქტი დაბრუნდება Active სტატუსით',
        type: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, restore it!'
    }).then(function () {
        $.ajax({
            url: "{{ url('products') }}/" + id + "/restore",
            type: "POST",
            data: {'_token': csrf_token},
            success: function(data) {
                table.ajax.reload();
                swal("Restored!", data.message, "success");
            },
            error: function() {
                swal("Oops...", "Something went wrong!", "error");
            }
        });
    });
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
@endsection
