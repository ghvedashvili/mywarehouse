@extends('layouts.master')

@section('top')
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@endsection

@section('content')
    <div class="box box-success">
        <div class="box-header">
            <h3 class="box-title">Outgoing Products</h3>
            <div class="pull-right">
                <a onclick="addSaleForm()" class="btn btn-success"><i class="fa fa-plus"></i> Add New Sale</a>
                <a href="{{ route('exportPDF.productOrderAll') }}" class="btn btn-danger">Export PDF</a>
            </div>
        </div>
        <div class="box-body">
            <table id="products-out-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Prices (GE/US)</th>
                        <th>Status</th>
                       
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @include('product_Order.form_sale')
@endsection

@section('bot')
    <script src="{{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/validator/validator.min.js') }}"></script>

    <script type="text/javascript">
        var save_method;
        var table = $('#products-out-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('api.productsOut') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'products_name', name: 'products_name'},
                {data: 'customer_name', name: 'customer_name'},
                {data: 'prices', name: 'prices'},
                {data: 'status_label', name: 'status_label'},

                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // --- კურიერის მართვა (Sale) ---
        function updateCourierPrices() {
            let localPriceFromDb = parseFloat($('#db_tbilisi_price').val()) || 0;

            if ($('#is_local_courier').is(':checked')) {
                $('#courier_price_tbilisi').val(localPriceFromDb);
            } else {
                $('#courier_price_tbilisi').val(0);
            }
            calculateSaleSummary();
        }

        $(document).on('change', '#is_local_courier', updateCourierPrices);

        // --- ჯამური გამოთვლა (Sale) ---
        function calculateSaleSummary() {
            var priceGe = parseFloat($('#price_georgia_sale').val()) || 0;
            var discount = parseFloat($('#discount_sale').val()) || 0;

            if (discount > priceGe) {
                discount = priceGe;
                $('#discount_sale').val(priceGe);
            }

            var totalToPay = priceGe - discount;

            var paid = (parseFloat($('#modal-sale input[name="paid_tbc"]').val()) || 0) + 
                       (parseFloat($('#modal-sale input[name="paid_bog"]').val()) || 0) + 
                       (parseFloat($('#modal-sale input[name="paid_lib"]').val()) || 0) + 
                       (parseFloat($('#modal-sale input[name="paid_cash"]').val()) || 0);
            
            var diff = paid - totalToPay;
            var summary = $('#sale_summary_text');

            if (priceGe === 0 && paid === 0) {
                summary.text('შეიყვანეთ მონაცემები').css('color', 'black');
            } else if (diff < -0.01) {
                summary.text('აკლია: ' + Math.abs(diff).toFixed(2) + ' ₾ (გადასახდელია: ' + totalToPay.toFixed(2) + ')').css('color', 'red');
            } else if (diff > 0.01) {
                summary.text('ზედმეტია: ' + diff.toFixed(2) + ' ₾').css('color', 'green');
            } else {
                summary.text('სრულად გადახდილია (' + totalToPay.toFixed(2) + ' ₾)').css('color', 'green');
            }
        }

        $(document).on('input', '#modal-sale input[name^="paid_"], #price_georgia_sale, #discount_sale', calculateSaleSummary);

        // --- Sale ფორმის მართვა ---
        function addSaleForm() {
            save_method = "add";
            $('input[name=_method]').val('POST');
            $('#modal-sale').modal('show');
            $('#form-sale-content')[0].reset();
            $('#product_image_preview').hide(); // სურათის დამალვა
            $('#modal-sale .modal-title').text('Add New Sale');
            $('#sale_summary_text').text('შეიყვანეთ მონაცემები').css('color', 'black');

            let intlPrice = parseFloat($('#db_intl_price').val()) || 30;
            $('#courier_price_international').val(intlPrice);

            updateCourierPrices(); 
        }

        function editForm(id) {
            save_method = 'edit';
            $('input[name=_method]').val('PATCH');
            
            $.ajax({
                url: "{{ url('productsOut') }}/" + id + "/edit",
                type: "GET",
                dataType: "JSON",
                success: function(data) {
                    $('#modal-sale').modal('show');
                    $('#modal-sale .modal-title').text('Edit Sale');
                    
                    $('#modal-sale input[name="id"]').val(data.id);
                    $('#product_id_sale').val(data.product_id).trigger('change');
                    $('#price_georgia_sale').val(data.price_georgia);
                    $('#discount_sale').val(data.discount || 0);
                    
                    $('#courier_price_international').val(data.courier_price_international);
                    $('#is_local_courier').prop('checked', data.courier_price_tbilisi > 0);
                    
                    $('#modal-sale input[name="paid_tbc"]').val(data.paid_tbc);
                    $('#modal-sale input[name="paid_bog"]').val(data.paid_bog);
                    $('#modal-sale input[name="paid_lib"]').val(data.paid_lib);
                    $('#modal-sale input[name="paid_cash"]').val(data.paid_cash);
                    $('#product_id_sale').val(data.product_id).trigger('change');
                    updateCourierPrices();
                }
            });
        }

      $(document).on('change', '#product_id_sale', function () {

    const selected = $(this).find('option:selected');

    // --- ფასები ---
    $('#price_georgia_sale').val(selected.data('price-ge') || '');
    $('#price_usa_sale').val(selected.data('price-us') || '');

    // --- სურათი (ახალი UI-სთვის) ---
    const imageUrl = selected.data('image');
    const targetImg = $('#target_image');
    const noImageText = $('#no_image_text');

    if (imageUrl && imageUrl !== 'undefined' && $(this).val() !== '') {
        targetImg
            .attr('src', imageUrl)
            .fadeIn(200);

        noImageText.hide();
    } else {
        targetImg.hide().attr('src', '');
        noImageText.show();
    }

    // --- ზომები ---
    const sizesRaw = selected.data('sizes');
    const sizeSelect = $('#size_sale');

    sizeSelect.empty();

    if (sizesRaw && sizesRaw.toString().trim() !== '') {

        sizeSelect.append('<option value="">-- Select Size --</option>');

        sizesRaw.toString().split(',').forEach(function (size) {
            let s = size.trim();
            if (s !== '') {
                sizeSelect.append(`<option value="${s}">${s}</option>`);
            }
        });

        sizeSelect.prop('required', true);

    } else {
        sizeSelect.append('<option value="">-- No Size --</option>');
        sizeSelect.prop('required', false);
    }

    // --- summary recalculation ---
    calculateSaleSummary();
});
        $(document).on('submit', '.modal form', function (e) {
            if (!e.isDefaultPrevented()){
                e.preventDefault();
                var form = $(this);
                var modal = form.closest('.modal');
                var id = form.find('input[name="id"]').val();
                var url = (save_method == 'add') ? "{{ url('productsOut') }}" : "{{ url('productsOut') }}/" + id;

                $.ajax({
                    url : url,
                    type : "POST",
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    success : function(data) {
                        modal.modal('hide');
                        table.ajax.reload();
                        swal("წარმატება!", data.message, "success");
                    },
                    error : function(data) {
                // თუ ბექენდმა დააბრუნა 422 შეცდომა (ჩვენი ვალიდაცია)
                if (data.status === 422) {
                    var response = JSON.parse(data.responseText);
                    swal("შეცდომა", response.message, "error");
                } else {
                    swal("შეცდომა", "მონაცემები ვერ შეინახა", "error");
                }
            }
                });
                return false;
            }
        });

        function deleteData(id){
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            swal({
                title: 'დარწმუნებული ხართ?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'დიახ, წაშალე!'
            }).then(function () {
                $.ajax({
                    url : "{{ url('productsOut') }}/" + id,
                    type : "POST",
                    data : {'_method' : 'DELETE', '_token' : csrf_token},
                    success : function(data) {
                        table.ajax.reload();
                        swal("წაშლილია!", data.message, "success");
                    }
                });
            });
        }
    </script>
@endsection