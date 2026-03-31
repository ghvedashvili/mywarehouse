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
    <a onclick="mergeSelected()" class="btn btn-info" id="btn-merge" style="display:none;">
    <i class="fa fa-link"></i> გაერთიანება
</a>
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
                <thead class="fs-1">
    <tr>
        <th><input type="checkbox" id="check-all" title="ყველას მონიშვნა"></th>
        <th></th>  {{-- expand ღილაკი --}}
        <th>Status</th>
        <th>Date</th>
        <th>Picture</th>
        <th>Product</th>
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
              <button type="button" id="btn-send-mail" onclick="sendMail()" class="btn btn-success">
    <i class="fa fa-paper-plane"></i> გაგზავნა
</button>
               
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-status-log" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gray">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-history"></i> სტატუსის ისტორია</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>თარიღი</th>
                            <th>იყო</th>
                            <th>გახდა</th>
                            <th>შეცვალა</th>
                        </tr>
                    </thead>
                    <tbody id="status-log-body">
                        <tr><td colspan="4" class="text-center">იტვირთება...</td></tr>
                    </tbody>
                </table>
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
    {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data) {
            // გაერთიანებული შვილები ვერ ირჩევა
            if (data.merged_id && !data.is_primary) return '';
            return '<input type="checkbox" class="row-check" data-id="' + data.id + '" data-status="' + data.status_id + '">';
        }
    },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data) {
            if (data.is_primary) {
                return '<button class="btn btn-xs btn-default expand-btn" data-id="' + data.id + '" data-children=\'' + data.children_json + '\'>' +
                       '<i class="fa fa-chevron-right"></i></button>';
            }
            return '';
        }
    },
    {data: 'status_label',     name: 'status_label',     orderable: false, searchable: false},
    {data: 'created_at',       name: 'created_at',
        render: function(data) {
            if (data) {
                let d = new Date(data);
                return ("0"+d.getDate()).slice(-2) + '.' + ("0"+(d.getMonth()+1)).slice(-2) + '.' + d.getFullYear();
            }
            return '';
        }
    },
    {data: 'show_photo',       name: 'show_photo',       orderable: false, searchable: false},
    {data: 'product_info',     name: 'product_info',     orderable: false, searchable: false},
    {data: 'customer_name',    name: 'customer_name'},
    {data: 'prices',           name: 'prices',           orderable: false, searchable: false},
    {data: 'payment',          name: 'payment',          orderable: false, searchable: false},
    {data: 'customer_contact', name: 'customer_contact', orderable: false, searchable: true},
];

if (isAdmin) {
    columns.push({data: 'action', name: 'action', orderable: false, searchable: false});
}

// if (isAdmin) {
//     columns.push({data: 'action', name: 'action', orderable: false, searchable: false});
// }

var table = $('#products-out-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('api.productsOut') }}",
    columns: columns,
    order: [[1, 'desc']],
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
        $('input[name="courier_type"][value="tbilisi"]').prop('checked', true);
    } else {
        $('input[name="courier_type"][value="none"]').prop('checked', true);
    }
        });

        // =====================
        // კურიერი — radio buttons
        // =====================
        // courier_type radio-ს ცვლილება არ საჭიროებს დამატებით ლოგიკას —
        // მნიშვნელობა პირდაპირ იგზავნება სერვერზე name="courier_type"-ით

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

            var pSelect = $('#product_id_sale');
    var sSelect = $('#size_sale');
    // 2. ვხსნით ბლოკირებას (ეს ხაზები აკლდა)
    $('.edit-lock-msg').remove();
    pSelect.prop('disabled', false);
    sSelect.prop('disabled', false);

            $('#product_id_sale').val('').trigger('change');
            $('#size_sale').empty().append('<option value="">-- Size --</option>');
            $('#target_image').hide();
            $('#no_image_text').show();
            $('#customer_id_sale').val('').trigger('change');
            $('#customer_info_fields').hide();
            $('input[name="courier_type"][value="none"]').prop('checked', true);
            $('#modal-sale').modal('show');
        }

        // =====================
        // Edit Sale
        // =====================
     // =====================
// Edit Sale
// =====================
function editForm(id) {
    save_method = 'edit';
    $('#form-sale-content input[name=_method]').val('PATCH');

    $.ajax({
        url: "{{ url('productsOut') }}/" + id + "/edit",
        type: "GET",
        dataType: "JSON",
        success: function(data) {
            // 1. ფორმის რესეტი და ვიზუალი
            $('#form-sale-content')[0].reset();
            $('#modal-sale .modal-title').text('Edit Sale');
            
            // 2. ID და დამალული ფასები
            $('#modal-sale input[name="id"]').val(data.id);
            $('#price_georgia_sale').val(data.price_georgia);
            $('#price_usa_sale').val(data.price_usa);

            // 3. კლიენტი და სტატუსი
            $('#customer_id_sale').val(data.customer_id).trigger('change');
            $('#status_id_sale').val(data.status_id);

           // 🔒 პროდუქტის და ზომის ბლოკირების ლოგიკა
var statusId = data.status_id ? parseInt(data.status_id) : 1;
var pSelect = $('#product_id_sale');
var sSelect = $('#size_sale');

// ყოველთვის თავიდან გავხსნათ, რომ "Add"-ის დროს პრობლემა არ იყოს
pSelect.prop('disabled', false);
sSelect.prop('disabled', false);
$('.edit-lock-msg').remove(); 

// ვბლოკავთ მხოლოდ იმ შემთხვევაში, თუ სტატუსი არსებობს და არ არის "ახალი" (1)
if (data.id && statusId > 1) { 
    pSelect.prop('disabled', true);
    sSelect.prop('disabled', true);
    pSelect.closest('.form-group').find('label').append(' <span class="edit-lock-msg text-danger small">(Locked)</span>');
}

            // 4. ბანკები და ფასდაკლება
            $('#modal-sale input[name="paid_tbc"]').val(data.paid_tbc || 0);
            $('#modal-sale input[name="paid_bog"]').val(data.paid_bog || 0);
            $('#modal-sale input[name="paid_lib"]').val(data.paid_lib || 0);
            $('#modal-sale input[name="paid_cash"]').val(data.paid_cash || 0);
            $('#discount_sale').val(data.discount || 0);

            // 5. კურიერის ლოგიკა
            var courierVal = data.courier_servise_local || 'none';
            $('input[name="courier_type"][value="' + courierVal + '"]').prop('checked', true).trigger('change');

            // 6. პროდუქტის სინქრონიზაცია (ინაქტიურის გათვალისწინებით)
            var cp = data.current_product;
            if (cp && cp.product_status == 0) {
                pSelect.find('option[data-inactive="1"]').remove();
                var inactiveOption = new Option(cp.name + ' (Inactive)', cp.id, true, true);
                $(inactiveOption).attr({
                    'data-inactive': '1',
                    'data-price-ge': cp.price_geo,
                    'data-price-us': cp.price_usa,
                    'data-sizes': cp.sizes || '',
                    'data-image': cp.image || ''
                });
                pSelect.append(inactiveOption).trigger('change');
            } else {
                pSelect.val(data.product_id).trigger('change');
            }

            // ✨ ზომის ჩატვირთვის ლოდინი
            var checkSizeExist = setInterval(function() {
                if ($('#size_sale option').length > 1) {
                    $('#size_sale').val(data.product_size);
                    
                    if (typeof calculateSaleSummary === "function") {
                        calculateSaleSummary();
                    }
                    clearInterval(checkSizeExist);
                }
            }, 100);

            setTimeout(function() { clearInterval(checkSizeExist); }, 2000);

            $('#modal-sale').modal('show');
        },
        error: function() {
            swal("შეცდომა", "მონაცემების წამოღება ვერ მოხერხდა", "error");
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
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა წაშლისას!';
                swal("შეცდომა", msg, "error");
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
// ახალი — modal-sale დაიხურა → inactive temp option გასუფთავება
$('#modal-sale').on('hidden.bs.modal', function() {
    $('#product_id_sale option[data-inactive="1"]').remove();
    if ($.fn.select2 && $('#product_id_sale').hasClass('select2-hidden-accessible')) {
        $('#product_id_sale').trigger('change.select2');
    }
});
// =====================
// Quick Status Change
// =====================
function openStatusModal(orderId, currentStatusId) {
    let allowedStatuses = [];
    
    if (currentStatusId == 1) allowedStatuses = [1, 2]; // ახალი -> გზაში
    if (currentStatusId == 2) allowedStatuses = [1, 2, 3]; // გზაში -> ახალი ან საწყობი
    if (currentStatusId == 3) allowedStatuses = [2, 3, 4]; // საწყობი -> გზაში ან კურიერი
    if (currentStatusId == 4) allowedStatuses = [3, 4]; // კურიერი -> საწყობი

    $('#statusSelect option').each(function() {
        let val = $(this).val();
        if (allowedStatuses.includes(parseInt(val))) {
            $(this).show().prop('disabled', false);
        } else {
            $(this).hide().prop('disabled', true);
        }
    });
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
// real-time შემოწმება
$(document).on('input', '#mail_email_input', function() {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var val = $(this).val().trim();
    if (val === '' || emailRegex.test(val)) {
        $(this).css('border-color', '');
        $('#btn-send-mail').prop('disabled', false);
    } else {
        $(this).css('border-color', 'red');
        $('#btn-send-mail').prop('disabled', true);
    }
});
function sendMail() {
    var orderId    = $('#mail_order_id').val();
    var customerId = $('#mail_customer_id').val();
    var email      = $('#mail_email_input').val().trim();
    var origEmail  = $('#mail_original_email').val().trim();
    var subject    = $('#mail_subject').val().trim();
    var body       = $('#mail_body').val().trim();

    // ფორმატის შემოწმება
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
        $('#mail_email_input').css('border-color', 'red');
        swal("შეცდომა", "გთხოვთ შეიყვანოთ email მისამართი", "error");
        return;
    }
    if (!emailRegex.test(email)) {
        $('#mail_email_input').css('border-color', 'red');
        swal("შეცდომა", "email მისამართის ფორმატი არასწორია", "error");
        return;
    }

    $('#mail_email_input').css('border-color', '');

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
    var btn  = $('#btn-send-mail');

    // პატარა delay რომ browser-მა მოასწროს render
    btn.prop('disabled', true)
       .html('<i class="fa fa-spinner fa-spin"></i> იგზავნება...');

    setTimeout(function() {
        $.ajax({
            url: "{{ url('productsOut') }}/" + orderId + "/sendMail",
            type: "POST",
            data: {
                _token:      csrf,
                email:       email,
                subject:     subject,
                body:        body,
                save_email:  saveEmail ? 1 : 0,
                customer_id: customerId
            },
            success: function(data) {
                btn.prop('disabled', false)
                   .html('<i class="fa fa-paper-plane"></i> გაგზავნა');

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
                btn.prop('disabled', false)
                   .html('<i class="fa fa-paper-plane"></i> გაგზავნა');

                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'მეილი ვერ გაიგზავნა';
                swal("შეცდომა", msg, "error");
            }
        });
    }, 50); // 50ms საკმარისია render-ისთვის
}
function showStatusLog(orderId) {
    $('#status-log-body').html('<tr><td colspan="4" class="text-center">იტვირთება...</td></tr>');
    $('#modal-status-log').modal('show');

    $.get('/product-order/' + orderId + '/status-log', function(logs) {
        if (logs.length === 0) {
            $('#status-log-body').html('<tr><td colspan="4" class="text-center text-muted">ისტორია არ არის</td></tr>');
            return;
        }

        let html = '';
        logs.forEach(function(log) {
            const from = log.from_status
                ? '<span class="label label-' + log.from_status.color + '">' + log.from_status.name + '</span>'
                : '<span class="text-muted">—</span>';

            const to = '<span class="label label-' + log.to_status.color + '">' + log.to_status.name + '</span>';

            html += `<tr>
                <td>${log.changed_at}</td>
                <td>${from}</td>
                <td>${to}</td>
                <td>${log.user ? log.user.name : '—'}</td>
            </tr>`;
        });

        $('#status-log-body').html(html);
    });
}

// =====================
// Checkbox — ყველას მონიშვნა
// =====================
$(document).on('change', '#check-all', function() {
    $('.row-check').prop('checked', $(this).is(':checked'));
    toggleMergeBtn();
});

$(document).on('change', '.row-check', function() {
    toggleMergeBtn();
});

function toggleMergeBtn() {
    var checked = $('.row-check:checked');
    var count   = checked.length;

    if (count >= 2) {
        // ყველა მონიშნული status_id=3 უნდა იყოს
        var allStatus3 = true;
        checked.each(function() {
            if ($(this).data('status') != 3) allStatus3 = false;
        });
        $('#btn-merge').toggle(allStatus3);
    } else {
        $('#btn-merge').hide();
    }
}

// =====================
// Merge — გაერთიანება
// =====================
function mergeSelected() {
    var ids = [];
    $('.row-check:checked').each(function() {
        ids.push($(this).data('id'));
    });

    if (ids.length < 2) {
        swal("ინფო", "მინიმუმ 2 ორდერი აირჩიე", "info");
        return;
    }

    swal({
        title: 'გაერთიანება?',
        text: ids.length + ' ორდერი გაერთიანდება. პირველი (#' + ids[0] + ') იქნება მთავარი.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ, გავაერთიანო',
        cancelButtonText: 'გაუქმება'
    }).then(function() {
        // ✅ SweetAlert v1-ში .then() პირდაპირ იძახება დადასტურებისას
        // result.value შემოწმება არ არის საჭირო
        $.ajax({
            url: "{{ url('productsOut/merge') }}",
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                ids:    ids
            },
            success: function(data) {
                table.ajax.reload(null, false);
                $('#btn-merge').hide();
                $('#check-all').prop('checked', false);
                swal("წარმატება!", data.message, "success");
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "ვერ გაერთიანდა", "error");
            }
        });
    });
}

// =====================
// Unmerge — გაყოფა
// =====================
function unmergeOrder(id) {
    swal({
        title: 'გაყოფა?',
        text: 'გაერთიანება გაუქმდება და ყველა ორდერი დამოუკიდებელი გახდება.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'დიახ',
        cancelButtonText: 'გაუქმება'
    }).then(function() {
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/unmerge",
            type: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                table.ajax.reload(null, false);
                swal("წარმატება!", data.message, "success");
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "ვერ გაიყო", "error");
            }
        });
    });
}

// =====================
// Expand / Collapse — შვილების გაშლა
// =====================
$(document).on('click', '.expand-btn', function() {
    var btn       = $(this);
    var parentId  = btn.data('id');
    var children  = btn.data('children');
    var icon      = btn.find('i');
    var parentRow = btn.closest('tr');

    if (btn.hasClass('expanded')) {
        btn.removeClass('expanded');
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        $('tr.child-row-' + parentId).remove();
        return;
    }

    btn.addClass('expanded');
    icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');

    if (!children || children.length === 0) return;

    children.forEach(function(child) {
        var img = child.product_image
            ? '<img src="' + child.product_image + '" style="width:45px;height:45px;object-fit:cover;border-radius:3px;">'
            : '<span class="label label-default">No Img</span>';

        var statusBadge = '<span class="label label-' + child.status_color + '">' + child.status_name + '</span>';
        if (isAdmin) {
            statusBadge += ' <span class="label label-default" style="cursor:pointer;" onclick="openStatusModal(' + child.id + ',' + child.status_id + ')"><i class="fa fa-pencil"></i></span>';
        }

        // prices
        var prices = '<b>GE:</b> ' + child.price_georgia + ' ₾';
        if (isAdmin) prices += '<br><b>US:</b> ' + child.price_usa + ' $';

        // payment
        var payment = '<span style="color:' + child.payment_color + '; font-weight:bold;">' + child.payment + '</span>';

        // contact
        var contact = '<small>' +
            '<i class="fa fa-map-marker"></i> ' + child.customer_city + ', ' + child.customer_address + '<br>' +
            '<i class="fa fa-phone"></i> ' + child.customer_tel +
            (child.customer_alt ? ' / ' + child.customer_alt : '') +
            '</small>';

        // actions
        var actions = isAdmin
            ? '<a onclick="showStatusLog(' + child.id + ')" class="btn btn-warning btn-xs"><i class="fa fa-history"></i></a> '
            : '';

        var row = '<tr class="child-row-' + parentId + '" style="background:#fffde7;">' +
            '<td></td>' +
            '<td style="padding-left:20px;"><i class="fa fa-level-up fa-rotate-90" style="color:#aaa;"></i></td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + child.created_at + '</td>' +
            '<td>' + img + '</td>' +
            '<td><div>' + child.product_name + '</div>' +
                '<small class="text-muted">' + child.product_code + '</small>' +
                (child.product_size ? ' <span class="label label-info">' + child.product_size + '</span>' : '') +
            '</td>' +
            '<td>' + child.customer_name + '</td>' +
            '<td>' + prices + '</td>' +
            '<td>' + payment + '</td>' +
            '<td>' + contact + '</td>' +
            (isAdmin ? '<td>' + actions + '</td>' : '') +
        '</tr>';

        parentRow.after(row);
    });
});

// table reload-ისას გაშლილი სტრიქონები გაქრება — ეს ნორმალურია
table.on('draw', function() {
    $('#check-all').prop('checked', false);
    $('#btn-merge').hide();
});

// =====================
// Merge სტატუს განახლება (primary → ყველა შვილი id=4)
// =====================
function mergeUpdateStatus(primaryId, mergedId) {
    swal({
        title: 'კურიერთან გაგზავნა?',
        text: 'ყველა დაჯგუფებული ორდერი გადავა "კურიერთან" სტატუსში.',
        type: 'question',
        showCancelButton: true,
        confirmButtonText: 'დიახ',
        cancelButtonText: 'გაუქმება'
    }).then(function() {
        $.ajax({
            url: "{{ url('productsOut/mergeStatus') }}",
            type: "POST",
            data: {
                _token:    $('meta[name="csrf-token"]').attr('content'),
                merged_id: mergedId,
                status_id: 4
            },
            success: function(data) {
                table.ajax.reload(null, false);

                var pdfUrl = "{{ url('exportProductOrder') }}/" + primaryId;

                swal({
                    title: '✅ კურიერს გადაეცა!',
                    type: 'success',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'დახურვა',
                    html: 'გსურთ ორდერის დაბეჭდვა?<br><br>' +
                          '<a href="' + pdfUrl + '" target="_blank" ' +
                          'class="btn btn-success" ' +
                          'onclick="swal.close()">' +
                          '<i class="fa fa-print"></i> დაბეჭდვა' +
                          '</a>'
                });
            },
            error: function(xhr) {
                swal("შეცდომა", xhr.responseJSON ? xhr.responseJSON.message : "შეცდომა", "error");
            }
        });
    });
}

// როცა მომხმარებელი აჭერს Save-ს
$('#form-sale-content').on('submit', function() {
    // დროებით ვააქტიურებთ დაბლოკილ ველებს გაგზავნისთვის
    $(this).find(':disabled').prop('disabled', false);
});

window.sendSingleToCourier = function(id) {
    swal({
        title: 'კურიერთან გაგზავნა?',
        text: 'ორდერი #' + id + ' კურიერს გადაეცემა',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00a65a',
        cancelButtonText: 'გაუქმება',
        confirmButtonText: 'დიახ, გაგზავნა!'
    }).then(function() {
        $.ajax({
            url: "{{ url('productsOut') }}/" + id + "/send-to-courier",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
    table.ajax.reload();

    var pdfUrl = "{{ url('exportProductOrder') }}/" + id;

    swal({
        title: '✅ კურიერს გადაეცა!',
        text: 'გსურთ ორდერის დაბეჭდვა?',
        type: 'success',
        showCancelButton: true,
        cancelButtonText: 'არა',
        confirmButtonText: 'დაბეჭდვა',
        // ── confirm ღილაკის ნაცვლად HTML link ──
        html: 'გსურთ ორდერის დაბეჭდვა?<br><br>' +
              '<a href="' + pdfUrl + '" target="_blank" ' +
              'class="btn btn-success" ' +
              'onclick="swal.close()">' +
              '<i class="fa fa-print"></i> დაბეჭდვა' +
              '</a>',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'დახურვა'
    });
},
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა!';
                swal('შეცდომა', msg, 'error');
            }
        });
    });
};
    </script>
@endsection