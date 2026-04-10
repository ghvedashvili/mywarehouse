@extends('layouts.master')

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
<style>
:root {
    --wh-green:  #00a65a;
    --wh-orange: #f39c12;
    --wh-red:    #dd4b39;
    --wh-blue:   #357ca5;
    --wh-dark:   #222d32;
    --wh-border: #dee2e6;
}
.wh-header {
    background: var(--wh-dark); color: #fff;
    padding: 18px 25px 14px; border-radius: 6px 6px 0 0;
    display: flex; align-items: center; justify-content: space-between;
}
.wh-header h3 { margin:0; font-size:17px; font-weight:700; }
.wh-header .wh-subtitle { font-size:11px; color:#aaa; margin-top:2px; }
.wh-table thead th {
    background:#f4f4f4; font-size:11px; text-transform:uppercase;
    letter-spacing:0.5px; color:#555;
    border-bottom:2px solid var(--wh-border) !important; white-space:nowrap;
}
</style>
@endsection

@section('content')
<div style="padding:15px 0;">

    <div class="wh-header">
        <div>
            <h3>📦 შესყიდვების ორდერები</h3>
            <div class="wh-subtitle">Purchase Orders Management</div>
        </div>
        <button onclick="openPurchaseModal()" class="btn btn-success btn-sm" style="font-weight:700;">
            <i class="fa fa-plus"></i> ახალი შესყიდვა
        </button>
    </div>

    <div style="margin-top:15px;">
        <table id="purchases-table" class="table wh-table table-hover table-bordered">
            <thead>
                <tr>
                    <th>ნომერი</th>
                    <th>პროდუქტი</th>
                    <th>კოდი</th>
                    <th>ზომა</th>
                    <th>რაოდ.</th>
                    <th>გადახდა ($)</th>
                    <th>Price (₾)</th>
                    <th>სტატუსი</th>
                    <th>თარიღი</th>
                    <th>მოქმედება</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

{{-- Status Modal --}}
<div class="modal fade" id="modal-status" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-gray-light">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="font-weight:bold;">სტატუსის შეცვლა</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_order_id">
                <div class="form-group">
                    <label>ახალი სტატუსი</label>
                    <select id="new_status_id" class="form-control">
                        @foreach($statuses as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-primary" onclick="submitStatus()">შენახვა</button>
            </div>
        </div>
    </div>
</div>

{{-- Partial Receive Modal --}}
<div class="modal fade" id="modal-partial-receive" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#f39c12; color:#fff; border-radius:8px 8px 0 0;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:1;">&times;</button>
                <h4 class="modal-title" style="font-weight:700;">📦 საწყობში მიღება</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="partial_purchase_id">
                <div style="background:#f9f9f9; border:1px solid #ddd; border-radius:6px;
                            padding:10px 14px; margin-bottom:14px; font-size:13px;">
                    <div><strong id="partial_product_name">—</strong></div>
                    <div style="margin-top:4px; color:#666;">
                        შეკვეთილი: <strong id="partial_total_qty">—</strong> ერთ.
                        &nbsp;|&nbsp;
                        ზომა: <strong id="partial_size">—</strong>
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight:600;">რამდენი ჩამოვიდა?</label>
                    <input type="number" id="partial_received_qty" class="form-control"
                           min="1" placeholder="0" style="font-size:18px; font-weight:700; text-align:center;">
                    <small class="text-muted" id="partial_remaining_text" style="display:block; margin-top:6px;"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success" onclick="submitPartialReceive()" id="btn-partial-save">
                    <i class="fa fa-check"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

@include('purchases.form_purchase')

@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>

<script>
$(function() {

    $('.select2-purchase').select2({ dropdownParent: $('#modal-purchase'), width: '100%' });

    // ══ PURCHASES TABLE ══
    var purchasesTable = $('#purchases-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('purchases.api') }}",
        columns: [
            { data: 'order_number',       name: 'order_number' },
            { data: 'product_name',       name: 'product_name' },
            { data: 'product_code',       name: 'product_code' },
            { data: 'product_size',       name: 'product_size' },
            { data: 'quantity',           name: 'quantity' },
            { data: 'payment',            name: 'payment',      orderable: false },
            { data: 'price_paid',         name: 'price_paid',   orderable: false },
            { data: 'status_name',        name: 'status_name',  orderable: false },
            { data: 'created_at',         name: 'created_at' },
            { data: 'action',             name: 'action',       orderable: false },
            { data: 'is_return_purchase', name: 'is_return_purchase', visible: false },
        ],
        createdRow: function(row, data) {
            if (data.is_return_purchase == 1) {
                $(row).css('background-color', '#d9edf7');
            }
        }
    });

    // ══ PRODUCT CHANGE ══
    $('#purchase_product_id').on('change', function() {
        var opt   = $(this).find(':selected');
        var sizes = opt.data('sizes') || '';
        var image = opt.data('image') || '';
        var geo   = opt.data('price-ge') || 0;

        var isNew = !$('#purchase_id').val();
        if (isNew) { $('#purchase_price_geo_input').val(geo); }

        $('#purchase_size').empty().append('<option value="">ზომა</option>');
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $('#purchase_size').append('<option value="' + s + '">' + s + '</option>');
            });
        }

        if (image) { $('#purchase_preview').attr('src', image).show(); $('#purchase_no_img').hide(); }
        else        { $('#purchase_preview').hide(); $('#purchase_no_img').show(); }

        $('#current-stock-info').hide();
        $('#fifo_current_block').hide();
        calcPurchaseSummary();
    });

    // ══ SIZE CHANGE ══
    $('#purchase_size').on('change', function() {
        var prodId = $('#purchase_product_id').val();
        var size   = $(this).val();
        if (prodId && size) loadStockInfo(prodId, size);
        else $('#current-stock-info').hide();
    });

    // ══ INPUT LISTENERS ══
    $('#purchase_price_usa_input').on('input', calcPurchaseSummary);
    $('#purchase_transport_input').on('input', function() {
        $('#purchase_transport_hidden').val($(this).val() || 0);
        calcPurchaseSummary();
    });
    $('#purchase_qty, #purchase_discount').on('input', calcPurchaseSummary);
    $(document).on('input', '.purchase-payment', calcPurchaseSummary);

    // ══ SUMMARY CALC ══
    function calcPurchaseSummary() {
        var usa       = parseFloat($('#purchase_price_usa_input').val()) || 0;
        var transport = parseFloat($('#purchase_transport_input').val()) || 0;
        var qty       = parseInt($('#purchase_qty').val()) || 1;
        var discount  = parseFloat($('#purchase_discount').val()) || 0;
        var tbc  = parseFloat($('[name="paid_tbc"]', '#form-purchase').val()) || 0;
        var bog  = parseFloat($('[name="paid_bog"]', '#form-purchase').val()) || 0;
        var lib  = parseFloat($('[name="paid_lib"]', '#form-purchase').val()) || 0;
        var cash = parseFloat($('[name="paid_cash"]', '#form-purchase').val()) || 0;

        var costPerUnit = usa + transport;
        $('#purchase_cost_price_display').text('$' + costPerUnit.toFixed(2));

        var total = (costPerUnit * qty) - discount;
        var paid  = tbc + bog + lib + cash;
        var diff  = total - paid;

        var color = diff > 0.01 ? 'red' : (diff < -0.01 ? 'green' : '#00a65a');
        var label = diff > 0.01
            ? '💳 დავალიანება: $' + diff.toFixed(2)
            : (diff < -0.01 ? '💚 ზედმეტი: $' + Math.abs(diff).toFixed(2) : '✅ გადახდილია');

        $('#purchase_summary_text').text(label).css('color', color);
    }

    // ══ STOCK INFO ══
    function loadStockInfo(productId, size) {
        $.get("{{ route('warehouse.stockInfo') }}", { product_id: productId, size: size }, function(data) {
            if (data.found) {
                $('#si-physical').text(data.physical_qty);
                $('#si-incoming').text(data.incoming_qty);
                $('#si-reserved').text(data.reserved_qty);
            } else {
                $('#si-physical, #si-incoming, #si-reserved').text(0);
            }
            $('#si-fifo-cost').text('$' + data.fifo_cost);
            $('#fifo_current_display').text('$' + data.fifo_cost);
            $('#fifo_current_block').show();
            $('#current-stock-info').show();
        });
    }

    // ══ MODAL OPEN ══
    window.openPurchaseModal = function() {
        $('#purchase_id').val('');
        $('input[name="_method"]', '#form-purchase').val('POST');
        $('#purchase-modal-title').text('📦 ახალი შესყიდვა');
        $('#form-purchase')[0].reset();
        $('.select2-purchase').val(null).trigger('change');
        $('#purchase_preview').hide();
        $('#purchase_no_img').show();
        $('#current-stock-info').hide();
        $('#fifo_current_block').hide();
        $('#purchase_size').empty().append('<option value="">ზომა</option>');
        $('#purchase_price_geo_text').text('0');
        $('#purchase_cost_price_display').text('$0.00');
        $('#purchase_transport_hidden').val(0);
        $('#purchase_summary_text').html('<span class="text-muted">შეიყვანეთ მონაცემები</span>');
        $('#modal-purchase').modal('show');
    };

    // ══ EDIT ══
    window.editPurchase = function(id) {
        $.get("{{ url('purchases') }}/" + id + "/edit", function(data) {
            $('#purchase_id').val(data.id);
            $('input[name="_method"]', '#form-purchase').val('PATCH');
            $('#purchase-modal-title').text('✏️ შესყიდვის რედაქტირება ' + (data.order_number || '#' + data.id));

            var opt   = $('#purchase_product_id option[value="' + data.product_id + '"]');
            var sizes = opt.data('sizes') || '';

            $('#purchase_size').empty().append('<option value="">ზომა</option>');
            if (sizes) {
                sizes.toString().split(',').forEach(function(s) {
                    s = s.trim();
                    if (s) $('#purchase_size').append('<option value="' + s + '">' + s + '</option>');
                });
            }

            $('#purchase_product_id').val(data.product_id).trigger('change.select2');

            var geo   = opt.data('price-ge') || data.price_georgia || 0;
            var image = opt.data('image') || '';
            $('#purchase_price_geo_text').text(geo);
            $('#purchase_price_georgia_hidden').val(geo);

            if (image) { $('#purchase_preview').attr('src', image).show(); $('#purchase_no_img').hide(); }
            else        { $('#purchase_preview').hide(); $('#purchase_no_img').show(); }

            $('#purchase_size').val(data.product_size);
            loadStockInfo(data.product_id, data.product_size);

            $('#purchase_qty').val(data.quantity);
            $('#purchase_price_usa_input').val(data.price_usa);
            $('#purchase_price_usa_hidden').val(data.price_usa);
            $('#purchase_price_geo_input').val(data.price_georgia || 0);

            var transport = data.courier_price_international || 0;
            $('#purchase_transport_input').val(transport);
            $('#purchase_transport_hidden').val(transport);

            $('#purchase_discount').val(data.discount || 0);
            $('#purchase_status_id').val(data.status_id);
            $('[name="paid_tbc"]', '#form-purchase').val(data.paid_tbc || 0);
            $('[name="paid_bog"]', '#form-purchase').val(data.paid_bog || 0);
            $('[name="paid_lib"]', '#form-purchase').val(data.paid_lib || 0);
            $('[name="paid_cash"]', '#form-purchase').val(data.paid_cash || 0);
            $('#purchase_comment').val(data.comment || '');

            // ─── lock თუ გაყიდვა მოხდა ──────────────────────────────────
            var courierCount = data.courier_count || 0;
            var $lockFields  = $('#purchase_product_id, #purchase_size, #purchase_price_usa_input, #purchase_transport_input');
            var $qtyField    = $('#purchase_qty');

            $lockFields.prop('disabled', false).css('background', '');
            $qtyField.removeAttr('min').css('background', '');
            $('#purchase-courier-lock-msg').remove();

            if (courierCount > 0) {
                $lockFields.prop('disabled', true).css('background', '#f5f5f5');
                $qtyField.attr('min', courierCount).css('background', '#fff8e1');
                var lockMsg = '<div id="purchase-courier-lock-msg" style="background:#fff3cd; border:1px solid #ffc107;' +
                    'border-radius:6px; padding:8px 12px; margin-bottom:10px; font-size:12px; color:#856404;">' +
                    '⚠️ <strong>' + courierCount + ' ერთეული</strong> გაყიდულია. ' +
                    'პროდუქტი / ზომა / ფასი / ტრანსპ. ვერ შეიცვლება.</div>';
                $('#form-purchase .modal-body').prepend(lockMsg);
            }

            calcPurchaseSummary();
            $('#modal-purchase').modal('show');
        });
    };

    $('#modal-purchase').on('hidden.bs.modal', function() {
        $('#purchase_product_id, #purchase_size, #purchase_price_usa_input, #purchase_transport_input')
            .prop('disabled', false).css('background', '');
        $('#purchase_qty').removeAttr('min').css('background', '');
        $('#purchase-courier-lock-msg').remove();
    });

    // ══ DELETE ══
    window.deletePurchase = function(id) {
        swal({
            title: 'დარწმუნებული ხარ?', text: 'შესყიდვა წაიშლება!',
            type: 'warning', showCancelButton: true,
            confirmButtonColor: '#dd4b39',
            cancelButtonText: 'გაუქმება', confirmButtonText: 'წაშლა'
        }).then(function() {
            $.ajax({
                url: "{{ url('purchases') }}/" + id, type: 'POST',
                data: { _method: 'DELETE', _token: "{{ csrf_token() }}" },
                success: function(res) {
                    purchasesTable.ajax.reload();
                    swal({ title: 'წაიშალა!', text: res.message, type: 'success', timer: 1500 });
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                    swal({ title: 'შეცდომა', text: msg, type: 'error' });
                }
            });
        });
    };

    // ══ SUBMIT ══
    $('#form-purchase').on('submit', function(e) {
        e.preventDefault();
        $('#purchase_price_usa_hidden').val($('#purchase_price_usa_input').val() || 0);
        $('#purchase_transport_hidden').val($('#purchase_transport_input').val() || 0);

        var $locked = $('#purchase_product_id, #purchase_size, #purchase_price_usa_input, #purchase_transport_input').filter(':disabled');
        $locked.prop('disabled', false);
        var formData = $(this).serialize();
        $locked.prop('disabled', true);

        var id  = $('#purchase_id').val();
        var url = id ? "{{ url('purchases') }}/" + id : "{{ url('purchases') }}";

        $.ajax({
            url: url, type: 'POST',
            data: formData,
            success: function(res) {
                $('#modal-purchase').modal('hide');
                purchasesTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 1800 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            }
        });
    });

    // ══ STATUS MODAL ══
    window.openStatusModal = function(orderId, currentStatus) {
        $.get("{{ url('purchases') }}/" + orderId + "/edit", function(data) {
            if (currentStatus == 2) {
                $('#partial_purchase_id').val(orderId);
                $('#partial_product_name').text(data.product_name || 'Purchase #' + orderId);
                $('#partial_size').text(data.product_size || '');
                $('#partial_total_qty').text(data.quantity);
                $('#partial_received_qty').val(data.quantity).attr('max', data.quantity);
                updatePartialRemaining(data.quantity, data.quantity);
                $('#modal-partial-receive').modal('show');
            } else {
                $('#status_order_id').val(orderId);
                $('#new_status_id').val(currentStatus);
                $('#modal-status').modal('show');
            }
        });
    };

    $('#partial_received_qty').on('input', function() {
        var total = parseInt($('#partial_total_qty').text()) || 0;
        var got   = parseInt($(this).val()) || 0;
        updatePartialRemaining(total, got);
    });

    function updatePartialRemaining(total, got) {
        var rem = total - got;
        var txt = '';
        if (got >= total) {
            txt = '<span style="color:#00a65a; font-weight:700;">✅ სრული მიღება — ყველა ' + total + ' ერთ. ჩამოვა</span>';
        } else if (got > 0) {
            txt = '<span style="color:#f39c12; font-weight:700;">⚠️ ' + rem + ' ერთ. კვლავ გზაში დარჩება (ახალი Purchase შეიქმნება)</span>';
        }
        $('#partial_remaining_text').html(txt);
    }

    window.submitPartialReceive = function() {
        var id  = $('#partial_purchase_id').val();
        var qty = parseInt($('#partial_received_qty').val());
        var max = parseInt($('#partial_total_qty').text());

        if (!qty || qty < 1) { swal('შეცდომა', 'შეიყვანეთ მიღებული რაოდენობა', 'error'); return; }
        if (qty > max)       { swal('შეცდომა', 'მიღებული (' + qty + ') მეტია შეკვეთილზე (' + max + ')', 'error'); return; }

        $('#btn-partial-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ url('purchases') }}/" + id + "/partial-receive",
            type: 'POST',
            data: { received_qty: qty, _token: "{{ csrf_token() }}" },
            success: function(res) {
                $('#modal-partial-receive').modal('hide');
                purchasesTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 2500 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-partial-save').prop('disabled', false).html('<i class="fa fa-check"></i> დადასტურება');
            }
        });
    };

    window.submitStatus = function() {
        var id     = $('#status_order_id').val();
        var status = $('#new_status_id').val();
        $.ajax({
            url: "{{ url('purchases/update-status') }}/" + id,
            type: 'POST',
            data: { status_id: status, _token: "{{ csrf_token() }}" },
            success: function(res) {
                $('#modal-status').modal('hide');
                purchasesTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 1500 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            }
        });
    };

});
</script>
@endsection