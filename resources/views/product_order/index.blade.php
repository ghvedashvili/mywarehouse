@extends('layouts.master')

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@endsection

@section('content')
    <div class="box box-success">
        <div class="box-header">
    <h3 class="box-title">Outgoing Products</h3>
    <div class="pull-right" style="display:flex; align-items:center; gap:12px;">

        {{-- Deleted სვიჩერი --}}
        <div style="display:inline-flex; align-items:center; gap:8px; vertical-align:middle;">
            <label for="toggle-show-deleted" style="font-size:13px; color:#666; margin:0; cursor:pointer;">წაშლილი</label>
            <label style="position:relative; display:inline-block; width:42px; height:24px; margin:0; cursor:pointer;">
                <input type="checkbox" id="toggle-show-deleted" style="opacity:0; width:0; height:0;">
                <span id="toggle-track-deleted" style="position:absolute; top:0; left:0; right:0; bottom:0; background:#ccc; border-radius:24px; transition:.3s;"></span>
                <span id="toggle-thumb-deleted" style="position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,0.3);"></span>
            </label>
        </div>

        <a onclick="addSaleForm()" class="btn btn-success"><i class="fa fa-plus"></i> Add New Sale</a>
        <a onclick="exportFilteredPDF()" class="btn btn-warning"><i class="fa fa-file-pdf-o"></i> Export Filtered PDF</a>
        <a href="{{ route('exportPDF.productOrderAll') }}" class="btn btn-danger">Export PDF</a>
    </div>
</div>
        <!-- <div class="box-header">
    <div style="display:inline-flex; align-items:center; gap:8px; margin-left:10px; vertical-align:middle;">
    <label for="toggle-deleted" style="font-size:13px; color:#666; margin:0; cursor:pointer;">დავალიანება</label>
    <label style="position:relative; display:inline-block; width:42px; height:24px; margin:0; cursor:pointer;">
        <input type="checkbox" id="toggle-deleted" style="opacity:0; width:0; height:0;">
        <span id="toggle-track" style="
            position:absolute; top:0; left:0; right:0; bottom:0;
            background:#ccc; border-radius:24px; transition:.3s;
        "></span>
        <span id="toggle-thumb" style="
            position:absolute; height:18px; width:18px;
            left:3px; bottom:3px; background:white;
            border-radius:50%; transition:.3s;
            box-shadow:0 1px 3px rgba(0,0,0,0.3);
        "></span>
    </label>
</div>
</div> -->
        <div class="box-body">
            <table id="products-out-table" class="table table-bordered table-striped">
                <thead>
    <tr>
        <th>Status</th>
        <!-- <th>Code</th> -->
         <th>Date</th>
        <th>Picture</th>
        <th>Product</th>
        <th>Product Code</th>
        <th>Size</th>
        <th>Customer</th>
        <th>Prices</th>
        <th>Payment</th>
        <th>Contact</th>
        @if(auth()->user()->role == 'admin')
        <th>Actions</th>
        @endif
    </tr>
</thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
<div class="modal fade" id="modal-status" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm"> <div class="modal-content" style="border-radius: 8px;">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Change Status</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_order_id">
                <div class="form-group">
                    <label>Select New Status</label>
                    <select id="quick_status_select" class="form-control">
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                <button type="button" onclick="saveQuickStatus()" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-image-preview" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="text-align: center; margin-top: 50px;">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            <div class="modal-body" style="position: relative; padding: 0;">
                <button type="button" class="close" data-dismiss="modal" 
                        style="color: #fff; opacity: 1; font-size: 45px; position: absolute; top: -45px; right: 0;">&times;</button>
                <img id="preview-img-full" src="" 
                     style="max-width: 100%; max-height: 85vh; border: 3px solid #fff; border-radius: 4px; box-shadow: 0 0 30px rgba(0,0,0,0.6);">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modal-mail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> მეილის გაგზავნა</h4>
            </div>
           <div class="modal-body">
    <input type="hidden" id="mail_order_id">
    <input type="hidden" id="mail_customer_id">
    <input type="hidden" id="mail_original_email">

    <div class="form-group">
        <label>Email მისამართი</label>
        <input type="email" id="mail_email_input" class="form-control" placeholder="example@gmail.com">
    </div>
    <div class="form-group">
        <label>სათაური</label>
        <input type="text" id="mail_subject" class="form-control" value="თქვენი შეკვეთის ინფორმაცია">
    </div>
    <div class="form-group">
        <label>შეტყობინება <small class="text-muted">(სურვილისამებრ)</small></label>
        <textarea id="mail_body" class="form-control" rows="3" placeholder="დამატებითი შეტყობინება..."></textarea>
    </div>

    {{-- PDF preview hint --}}
    <div style="background:#f9f9f9; border:1px solid #e0e0e0; border-radius:6px; padding:10px 12px; font-size:12px; color:#666;">
        <i class="fa fa-file-pdf-o" style="color:#c0392b;"></i>
        შეკვეთის <strong>Invoice PDF</strong> ავტომატურად დაემატება attachment-ად
    </div>
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">გაუქმება</button>
                <button type="button" onclick="sendMail()" class="btn btn-success">
                    <i class="fa fa-paper-plane"></i> გაგზავნა
                </button>
            </div>
        </div>
    </div>
</div>
    @include('product_Order.form_sale')
@endsection

@section('bot')
    <script src="{{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/validator/validator.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script type="text/javascript">

        // =====================
        // DataTable
        // =====================
        var save_method;
        var isAdmin = {{ auth()->user()->role == 'admin' ? 'true' : 'false' }};

var columns = [
    {data: 'status_label',    name: 'status_label',    orderable: false, searchable: false},
    // {data: 'order_id',        name: 'order_id'},
    {
        data: 'created_at', 
        name: 'created_at',
        render: function(data, type, row) {
            if (data) {
                let date = new Date(data);
                let day = ("0" + date.getDate()).slice(-2);
                let month = ("0" + (date.getMonth() + 1)).slice(-2);
                let year = date.getFullYear();
                return day + '.' + month + '.' + year; // ფორმატი: 24.03.2026
            }
            return '';
        }
    },
    {data: 'show_photo',      name: 'show_photo',      orderable: false, searchable: false},
    {data: 'products_name',   name: 'products_name'},
    {data: 'product_code',    name: 'product_code'},
    {data: 'product_size',    name: 'product_size'},
    {data: 'customer_name',   name: 'customer_name'},
    {data: 'prices',          name: 'prices',          orderable: false, searchable: false},
    {data: 'payment', name: 'payment', orderable: false, searchable: false},
    {data: 'customer_contact',name: 'customer_contact',orderable: false, searchable: true},
];

if (isAdmin) {
    columns.push({data: 'action', name: 'action', orderable: false, searchable: false});
}

var table = $('#products-out-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('api.productsOut') }}",
    columns: columns,
    rowCallback: function(row, data) {
        var geo  = parseFloat(data.price_georgia || 0) - parseFloat(data.discount || 0);
        var paid = parseFloat(data.paid_tbc || 0) + parseFloat(data.paid_bog || 0) +
                   parseFloat(data.paid_lib || 0) + parseFloat(data.paid_cash || 0);
        if ((geo - paid) > 0.01) {
            $(row).css('background-color', '#f2dede');
        } else {
            $(row).css('background-color', '');
        }
    },
    initComplete: function() {
    var switchHtml = `
        <div style="display:inline-flex; align-items:center; gap:8px; margin-left:15px; vertical-align:middle;">
            <label for="toggle-deleted" style="font-size:13px; color:#666; margin:0; cursor:pointer;">დავალიანება</label>
            <label style="position:relative; display:inline-block; width:42px; height:24px; margin:0; cursor:pointer;">
                <input type="checkbox" id="toggle-deleted" style="opacity:0; width:0; height:0;">
                <span id="toggle-track" style="position:absolute; top:0; left:0; right:0; bottom:0; background:#ccc; border-radius:24px; transition:.3s;"></span>
                <span id="toggle-thumb" style="position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,0.3);"></span>
            </label>
        </div>

        <div style="display:inline-flex; align-items:center; gap:6px; margin-left:15px; vertical-align:middle; position:relative;">
            <label style="font-size:13px; color:#666; margin:0;">სტატუსი:</label>
            <div id="status-filter-wrapper" style="position:relative;">
                <button id="status-filter-btn" type="button" style="
                    font-size:13px; padding:3px 10px; border:1px solid #ccc;
                    border-radius:4px; background:#fff; cursor:pointer; min-width:130px; text-align:left;">
                    ყველა სტატუსი <span style="float:right;">▾</span>
                </button>
                <div id="status-filter-dropdown" style="
                    display:none; position:absolute; top:100%; left:0; z-index:9999;
                    background:#fff; border:1px solid #ccc; border-radius:4px;
                    box-shadow:0 4px 12px rgba(0,0,0,0.15); min-width:180px; padding:6px 0;">
                    @foreach($statuses as $status)
                    <label style="display:flex; align-items:center; gap:8px; padding:5px 12px; cursor:pointer; font-size:13px; font-weight:normal; margin:0;">
                        <input type="checkbox" class="status-filter-check" value="{{ $status->id }}"> {{ $status->name }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>`;

    $('#products-out-table_length').append(switchHtml);
}
});
// $('#filter-debt').on('change', function() {
//     if ($(this).is(':checked')) {
//         table.ajax.url("{{ route('api.productsOut') }}?debt_only=1").load();
//     } else {
//         table.ajax.url("{{ route('api.productsOut') }}").load();
//     }
// });
        // =====================
        // Select2 — customer
        // =====================
        $('#customer_id_sale').select2({
            dropdownParent: $('#modal-sale'),
            placeholder: '-- Choose Customer --',
            allowClear: true
        });

        // customer info ჩვენება — მხოლოდ ერთხელ
        $('#customer_id_sale').on('change', function() {
            var selected = $(this).find('option:selected');

            if (!selected.val()) {
                $('#customer_info_fields').hide();
                return;
            }

            $('#customer_address').text((selected.data('city') || '') + ' - ' + (selected.data('address') || ''));
            $('#customer_tel').text(selected.data('tel') || '');
            $('#customer_alt_tel').text(selected.data('alt') || '');
            $('#customer_comment').text(selected.data('comment') || '');
            $('#customer_info_fields').show();

            var cityId = parseInt(selected.data('city-id'));
    if (cityId === 1) {
        $('#is_local_courier').prop('checked', true).trigger('change');
    } else {
        $('#is_local_courier').prop('checked', false).trigger('change');
    }
        });

        // =====================
        // კურიერი
        // =====================
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

        // =====================
        // ჯამური გამოთვლა
        // =====================
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

        // =====================
        // Add Sale
        // =====================
        function addSaleForm() {
            save_method = "add";
           $('#form-sale-content input[name=_method]').val('POST'); // შეიცვალა
            $('#form-sale-content')[0].reset();
            $('#modal-sale .modal-title').text('Add New Sale');
            $('#sale_summary_text').text('შეიყვანეთ მონაცემები').css('color', 'black');
            $('#product_id_sale').val('').trigger('change');
            $('#size_sale').empty().append('<option value="">-- Size --</option>');
            $('#target_image').hide();
            $('#no_image_text').show();
            $('#customer_id_sale').val('').trigger('change');
            $('#customer_info_fields').hide();
            updateCourierPrices();
            $('#modal-sale').modal('show');
        }

        // =====================
        // Edit Sale
        // =====================
        function editForm(id) {
            save_method = 'edit';
            $('#form-sale-content input[name=_method]').val('PATCH'); // შეიცვალა

            $.ajax({
                url: "{{ url('productsOut') }}/" + id + "/edit",
                type: "GET",
                dataType: "JSON",
                success: function(data) {
                    $('#form-sale-content')[0].reset();
                    $('#size_sale').empty().append('<option value="">-- Size --</option>');
                    $('#modal-sale .modal-title').text('Edit Sale');
                    $('#modal-sale input[name="id"]').val(data.id);
$('#status_id_sale').val(data.status_id);
                    $('#customer_id_sale').val(data.customer_id).trigger('change');

                    $('#price_georgia_sale').val(data.price_georgia);
                    $('#price_georgia_text').text(data.price_georgia);
                    $('#price_usa_sale').val(data.price_usa);
                    $('#price_usa_text').text(data.price_usa);
$('#is_local_courier').prop('checked', data.courier_servise_local >0);

// comment
$('#form-sale-content textarea[name="comment"]').val(data.comment || '');
                    let productSelect = $('#product_id_sale');
                    productSelect.one('productLoaded', function() {
                        $('#size_sale').val(data.product_size);
                    });
                    productSelect.val(data.product_id).trigger('change');

                    $('#discount_sale').val(data.discount || 0);
$('#modal-sale input[name="paid_tbc"]').val(data.paid_tbc);
$('#modal-sale input[name="paid_bog"]').val(data.paid_bog);
$('#modal-sale input[name="paid_lib"]').val(data.paid_lib);
$('#modal-sale input[name="paid_cash"]').val(data.paid_cash);
$('#is_local_courier').prop('checked', data.courier_price_tbilisi > 0); // ერთხელ
$('#form-sale-content textarea[name="comment"]').val(data.comment || ''); // ერთხელ
updateCourierPrices();
$('#modal-sale').modal('show');
                }
            });
        }

        // =====================
        // Product change
        // =====================
        $(document).on('change', '#product_id_sale', function() {
            const selected = $(this).find('option:selected');

            let priceGe = selected.data('price-ge') || 0;
            let priceUs = selected.data('price-us') || 0;

            $('#price_georgia_sale').val(priceGe);
            $('#price_georgia_text').text(priceGe);
            $('#price_usa_sale').val(priceUs);
            $('#price_usa_text').text(priceUs);

            const imageUrl = selected.data('image');
            if (imageUrl) {
                $('#target_image').attr('src', imageUrl).show();
                $('#no_image_text').hide();
            } else {
                $('#target_image').hide();
                $('#no_image_text').show();
            }

            const sizesRaw = selected.data('sizes');
            const sizeSelect = $('#size_sale');
            sizeSelect.empty();

            if (sizesRaw && sizesRaw.toString().trim() !== '') {
                sizeSelect.append('<option value="">-- Select Size --</option>');
                sizesRaw.toString().split(',').forEach(function(size) {
                    let s = size.trim();
                    if (s !== '') sizeSelect.append(`<option value="${s}">${s}</option>`);
                });
                sizeSelect.prop('required', true);
            } else {
                sizeSelect.append('<option value="">-- No Size --</option>');
                sizeSelect.prop('required', false);
            }

            calculateSaleSummary();
            $(this).trigger('productLoaded');
        });

        // =====================
        // Sale Form Submit
        // =====================
        $(document).on('submit', '#form-sale-content', function(e) {
            e.preventDefault();
            var form = $(this);
            var id = form.find('input[name="id"]').val();
            var url = (save_method == 'add') ? "{{ url('productsOut') }}" : "{{ url('productsOut') }}/" + id;

            $.ajax({
                url: url,
                type: "POST",
                data: new FormData(this),
                contentType: false,
                processData: false,
                success: function(data) {
                    $('#modal-sale').modal('hide');
                    table.ajax.reload();
                    swal("წარმატება!", data.message, "success");
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var response = JSON.parse(xhr.responseText);
                        swal("შეცდომა", response.message, "error");
                    } else {
                        swal("შეცდომა", "მონაცემები ვერ შეინახა", "error");
                    }
                }
            });
        });

        // =====================
        // Customer Form Submit — მხოლოდ ერთხელ
        // =====================
        $(document).on('submit', '#form-item', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                url: "{{ url('customers') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(data) {
                    $('#modal-form').modal('hide');

                    var newOption = new Option(
                        data.name + ' (' + data.tel + ')',
                        data.id,
                        true,
                        true
                    );
                    $(newOption).data('address', data.address || '');
                    $(newOption).data('city',    data.city_name || '');
                   $(newOption).data('city-id', data.city_id || 0);
                    $(newOption).data('tel',     data.tel || '');
                    $(newOption).data('alt',     data.alternative_tel || '');
                    $(newOption).data('comment', data.comment || '');

                    $('#customer_id_sale').append(newOption).trigger('change');
                    $('#form-item')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var msg = Object.values(errors).flat().join('\n');
                        swal("შეცდომა", msg, "error");
                    } else {
                        swal("შეცდომა", "ვერ შეინახა", "error");
                    }
                }
            });
        });

        // =====================
        // Delete
        // =====================
        function deleteData(id) {
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            swal({
                title: 'დარწმუნებული ხართ?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'დიახ, წაშალე!'
            }).then(function() {
                $.ajax({
                    url: "{{ url('productsOut') }}/" + id,
                    type: "POST",
                    data: {'_method': 'DELETE', '_token': csrf_token},
                    success: function(data) {
                        table.ajax.reload();
                        swal("წაშლილია!", data.message, "success");
                    }
                });
            });
        }

        // =====================
        // Customer Create Modal
        // =====================
        function openCustomerCreate() {
            $('#modal-sale').modal('hide');
            setTimeout(function() {
                $('#modal-form').modal('show');
            }, 400);
        }

        // modal-form დაიხურა → sale გახსნა
       $('#modal-form').on('hidden.bs.modal', function() {
    if ($('#modal-sale').length) {
        setTimeout(function() {
            $('#modal-sale').modal('show');
        }, 400);
    }
});
// =====================
// Quick Status Change
// =====================
function openStatusModal(orderId, currentStatusId) {
    $('#status_order_id').val(orderId);
    $('#quick_status_select').val(currentStatusId);
    $('#modal-status').modal('show');

    
}
// ==========================================
// სურათის გადიდების (Lightbox) ლოგიკა
// ==========================================
$(document).on('click', '.img-zoom-trigger', function() {
    // 1. ავიღოთ სურათის მისამართი (src)
    var imgSrc = $(this).attr('src');
    
    // 2. შევამოწმოთ, რომ სურათი ნამდვილად არსებობს და არ არის "no-image" placeholder
    if (!imgSrc || imgSrc.includes('no-image') || imgSrc.includes('placeholder')) {
        return; 
    }

    // 3. ჩავსვათ მისამართი მოდალის სურათში
    $('#preview-img-full').attr('src', imgSrc);

    // 4. გავხსნათ მოდალი
    $('#modal-image-preview').modal('show');
});

// სურვილისამებრ: მოდალის დახურვისას სურათის გასუფთავება (მხოლოდ ვიზუალური სისუფთავისთვის)
$('#modal-image-preview').on('hidden.bs.modal', function () {
    $('#preview-img-full').attr('src', '');
});

function saveQuickStatus() {
    var id       = $('#status_order_id').val();
    var statusId = $('#quick_status_select').val();
    var csrf     = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ url('productsOut') }}/" + id + "/status",
        type: "POST",
        data: {
            _method:   'PATCH',
            _token:    csrf,
            status_id: statusId
        },
        success: function(data) {
            $('#modal-status').modal('hide');
            table.ajax.reload(null, false); // false = პაგინაცია არ ბრუნდება
            // პატარა toast შეტყობინება სwal-ის ნაცვლად
            var toast = $('<div>')
                .text('✓ სტატუსი განახლდა')
                .css({
                    position: 'fixed', bottom: '20px', right: '20px',
                    background: '#27ae60', color: '#fff',
                    padding: '10px 20px', borderRadius: '6px',
                    fontSize: '13px', fontWeight: '600',
                    zIndex: 9999, boxShadow: '0 4px 15px rgba(0,0,0,0.2)'
                })
                .appendTo('body');
            setTimeout(function() { toast.fadeOut(300, function() { $(this).remove(); }); }, 2000);
        },
        error: function() {
            swal("შეცდომა", "სტატუსი ვერ შეიცვალა", "error");
        }
    });
}

function exportFilteredPDF() {
    // DataTable-ის ამჟამად ხილული რიგებიდან ID-ების აღება
    var ids = [];
    table.rows({ search: 'applied' }).data().each(function(row) {
        ids.push(row.id);
    });

    if (ids.length === 0) {
        swal("ინფო", "გაფილტრული ორდერი არ მოიძებნა", "info");
        return;
    }

    // POST ფორმით გაგზავნა (GET-ზე URL ძალიან გრძელი შეიძლება გახდეს)
    var form = $('<form method="POST" action="{{ route('exportPDF.productOrderFiltered') }}" target="_blank">');
    form.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
    ids.forEach(function(id) {
        form.append('<input type="hidden" name="ids[]" value="' + id + '">');
    });
    $('body').append(form);
    form.submit();
    form.remove();
}

$(document).on('change', '#toggle-deleted', function() {
    if ($(this).is(':checked')) {
        $('#toggle-track').css('background', '#e74c3c');
        $('#toggle-thumb').css('transform', 'translateX(18px)');
    } else {
        $('#toggle-track').css('background', '#ccc');
        $('#toggle-thumb').css('transform', 'translateX(0)');
    }
    reloadTableWithFilters();
});


// სტატუს ფილტრის dropdown გახსნა/დახურვა
$(document).on('click', '#status-filter-btn', function(e) {
    e.stopPropagation();
    $('#status-filter-dropdown').toggle();
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('#status-filter-wrapper').length) {
        $('#status-filter-dropdown').hide();
    }
});

// სტატუსების მიხედვით ფილტრაცია
$(document).on('change', '.status-filter-check', function() {
    var selected = [];
    $('.status-filter-check:checked').each(function() {
        selected.push($(this).val());
    });

    if (selected.length === 0) {
        $('#status-filter-btn').html('ყველა სტატუსი <span style="float:right;">▾</span>');
    } else {
        $('#status-filter-btn').html(selected.length + ' მონიშნული <span style="float:right;">▾</span>');
    }

    reloadTableWithFilters(); // ← ეს იყო პრობლემა, ძველი კოდი პირდაპირ URL-ს ადგენდა
});

$(document).on('change', '#toggle-show-deleted', function() {
    if ($(this).is(':checked')) {
        $('#toggle-track-deleted').css('background', '#e74c3c');
        $('#toggle-thumb-deleted').css('transform', 'translateX(18px)');
    } else {
        $('#toggle-track-deleted').css('background', '#ccc');
        $('#toggle-thumb-deleted').css('transform', 'translateX(0)');
    }
    reloadTableWithFilters();
});
function reloadTableWithFilters() {
    var params = [];
    if ($('#toggle-deleted').is(':checked'))      params.push('debt_only=1');
    if ($('#toggle-show-deleted').is(':checked')) params.push('show_deleted=1');

    var selected = [];
    $('.status-filter-check:checked').each(function() { selected.push($(this).val()); });
    if (selected.length) params.push('statuses[]=' + selected.join('&statuses[]='));

    table.ajax.url("{{ route('api.productsOut') }}?" + params.join('&')).load();
}

// index.blade.php - სკრიპტების ბოლოს

function restoreData(id) {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    
    swal({
        title: 'ნამდვილად გსურთ აღდგენა?',
        type: 'info',
        showCancelButton: true,
        confirmButtonText: 'დიახ, აღადგინე!',
        cancelButtonText: 'გაუქმება'
    }).then(function() {
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/restore",
            type: "POST",
            data: {'_token': csrf_token},
            success: function(data) {
                table.ajax.reload(null, false); // ცხრილის განახლება პაგინაციის შენარჩუნებით
                swal("აღდგენილია!", data.message, "success");
            },
            error: function() {
                swal("შეცდომა", "აღდგენა ვერ მოხერხდა", "error");
            }
        });
    });
}
// =====================
// Mail Modal
// =====================
function openMailModal(orderId, customerId, email) {
    $('#mail_order_id').val(orderId);
    $('#mail_customer_id').val(customerId);
    $('#mail_original_email').val(email);
    $('#mail_email_input').val(email);
    $('#mail_subject').val('თქვენი შეკვეთის ინფორმაცია #' + orderId);
    $('#mail_body').val('');
    $('#modal-mail').modal('show');
}

function sendMail() {
    var orderId    = $('#mail_order_id').val();
    var customerId = $('#mail_customer_id').val();
    var email      = $('#mail_email_input').val().trim();
    var origEmail  = $('#mail_original_email').val().trim();
    var subject    = $('#mail_subject').val().trim();
    var body       = $('#mail_body').val().trim();

    if (!email) {
        swal("შეცდომა", "გთხოვთ შეიყვანოთ email მისამართი", "error");
        return;
    }

    // email შეიცვალა? → შენახვის კითხვა
    if (email !== origEmail) {
        swal({
            title: 'შევინახო მეილი?',
            text: 'email "' + email + '" შეინახოს ამ კლიენტისთვის?',
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'დიახ, შევინახო',
            cancelButtonText: 'მხოლოდ გავგზავნო'
        }).then(function(result) {
            doSendMail(orderId, customerId, email, subject, body, result.value === true);
        });
    } else {
        doSendMail(orderId, customerId, email, subject, body, false);
    }
}

function doSendMail(orderId, customerId, email, subject, body, saveEmail) {
    var csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ url('productsOut') }}/" + orderId + "/sendMail",
        type: "POST",
        data: {
            _token:     csrf,
            email:      email,
            subject:    subject,
            body:       body,
            save_email: saveEmail ? 1 : 0,
            customer_id: customerId
        },
        success: function(data) {
            $('#modal-mail').modal('hide');
            if (saveEmail) {
                $('#mail_original_email').val(email);
                table.ajax.reload(null, false);
            }
            var toast = $('<div>')
                .text('✓ მეილი გაიგზავნა')
                .css({
                    position:'fixed', bottom:'20px', right:'20px',
                    background:'#27ae60', color:'#fff',
                    padding:'10px 20px', borderRadius:'6px',
                    fontSize:'13px', fontWeight:'600',
                    zIndex:9999, boxShadow:'0 4px 15px rgba(0,0,0,0.2)'
                }).appendTo('body');
            setTimeout(function() { toast.fadeOut(300, function(){ $(this).remove(); }); }, 2500);
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'მეილი ვერ გაიგზავნა';
            swal("შეცდომა", msg, "error");
        }
    });
}
    </script>
@endsection