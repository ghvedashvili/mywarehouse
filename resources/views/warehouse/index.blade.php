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
.wh-header h3 { margin:0; font-size:17px; font-weight:700; letter-spacing:0.5px; }
.wh-header .wh-subtitle { font-size:11px; color:#aaa; margin-top:2px; }

.stat-cards { display:flex; gap:12px; margin-bottom:20px; }
.stat-card {
    flex:1; background:#fff; border:1px solid var(--wh-border);
    border-radius:8px; padding:16px 20px;
    border-left:4px solid var(--wh-green);
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
}
.stat-card.orange { border-left-color: var(--wh-orange); }
.stat-card.blue   { border-left-color: var(--wh-blue); }
.stat-card.red    { border-left-color: var(--wh-red); }
.stat-card .val { font-size:26px; font-weight:800; color:var(--wh-dark); line-height:1; }
.stat-card .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.6px; margin-top:4px; }

.wh-tabs { display:flex; border-bottom:2px solid var(--wh-border); margin-bottom:20px; }
.wh-tab {
    padding:10px 22px; cursor:pointer; font-size:13px; font-weight:600;
    color:#666; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all 0.2s;
}
.wh-tab.active { color:var(--wh-green); border-bottom-color:var(--wh-green); }
.wh-tab:hover:not(.active) { color:var(--wh-dark); background:#f9f9f9; }

.wh-table thead th {
    background:#f4f4f4; font-size:11px; text-transform:uppercase;
    letter-spacing:0.5px; color:#555;
    border-bottom:2px solid var(--wh-border) !important; white-space:nowrap;
}
.qty-badge {
    display:inline-block; min-width:32px; text-align:center;
    font-weight:700; padding:2px 8px; border-radius:4px; font-size:13px;
}
.qty-physical  { background:#dff0d8; color:#3c763d; }
.qty-incoming  { background:#d9edf7; color:#31708f; }
.qty-reserved  { background:#fcf8e3; color:#8a6d3b; }
.qty-available { background:#222d32; color:#fff; }
.qty-zero      { background:#f2dede; color:#a94442; }
</style>
@endsection

@section('content')
<div style="padding:15px 0;">

    <div class="wh-header">
        <div>
            <h3>🏭 საწყობი</h3>
            <div class="wh-subtitle">Warehouse Management — ნაშთი & შესყიდვები</div>
        </div>
        <button onclick="openPurchaseModal()" class="btn btn-success btn-sm" style="font-weight:700;">
            <i class="fa fa-plus"></i> ახალი შესყიდვა
        </button>
    </div>

    <div class="stat-cards" style="margin-top:15px;">
        <div class="stat-card">
            <div class="val" id="stat-physical">—</div>
            <div class="lbl">📦 ფიზიკური ნაშთი</div>
        </div>
        <div class="stat-card orange">
            <div class="val" id="stat-incoming">—</div>
            <div class="lbl">🚚 გზაში</div>
        </div>
        <div class="stat-card blue">
            <div class="val" id="stat-reserved">—</div>
            <div class="lbl">🔒 დაჯავშნული</div>
        </div>
        <div class="stat-card red">
            <div class="val" id="stat-low">—</div>
            <div class="lbl">⚠️ მცირე ნაშთი</div>
        </div>
    </div>

    <div class="wh-tabs">
        <div class="wh-tab active" onclick="switchTab('stock', this)">
            <i class="fa fa-cubes"></i> ნაშთის ცხრილი
        </div>
        <div class="wh-tab" onclick="switchTab('purchases', this)">
            <i class="fa fa-shopping-cart"></i> შესყიდვების ისტორია
        </div>
    </div>

    <div id="tab-stock">
        <div class="box box-success" style="margin-bottom:0; border-top:none; border-radius:0 0 6px 6px;">
            <div class="box-body" style="padding:15px;">
                <table id="stock-table" class="table table-bordered table-hover wh-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>პროდუქტი</th>
                            <th>კოდი</th>
                            <th>ზომა</th>
                            <th>📦 ფიზიკური</th>
                            <th>🚚 მოსალოდნელი</th>
                            <th>🔒 დაჯავშნული</th>
                            <th>✅ ხელმისაწვდომი</th>
                            <th>სტატუსი</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-purchases" style="display:none;">
        <div class="box box-success" style="margin-bottom:0; border-top:none; border-radius:0 0 6px 6px;">
            <div class="box-body" style="padding:15px;">
                <table id="purchases-table" class="table table-bordered table-hover wh-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>პროდუქტი</th>
                            <th>კოდი</th>
                            <th>ზომა</th>
                            <th>რაოდ.</th>
                            <th>Price ($)</th>
                            <th>Price (₾)</th>
                            <th>გადახდილი</th>
                            <th>სტატუსი</th>
                            <th>თარიღი</th>
                            <th>მოქმედება</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="modal-status" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm"> <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">სტატუსის შეცვლა</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_order_id">
                <div class="form-group">
                    <label>აირჩიეთ ახალი სტატუსი:</label>
                    <select id="modal_status_id" class="form-control">
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">დახურვა</button>
                <button type="button" onclick="saveStatusUpdate()" class="btn btn-success">შენახვა</button>
            </div>
        </div>
    </div>
</div>
@include('warehouse.form_purchase')
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function() {

    $('.select2-purchase').select2({ dropdownParent: $('#modal-purchase') });

    // ══ STOCK TABLE ══
    var stockTable = $('#stock-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('warehouse.apiStock') }}",
        columns: [
            { data: 'product_name', name: 'product_name' },
            { data: 'product_code', name: 'product_code' },
            { data: 'size',         name: 'size' },
            { data: 'physical_qty', name: 'physical_qty',
              render: v => `<span class="qty-badge ${v>0?'qty-physical':'qty-zero'}">${v}</span>` },
            { data: 'incoming_qty', name: 'incoming_qty',
              render: v => `<span class="qty-badge ${v>0?'qty-incoming':'qty-zero'}">${v}</span>` },
            { data: 'reserved_qty', name: 'reserved_qty',
              render: v => `<span class="qty-badge ${v>0?'qty-reserved':'qty-zero'}">${v}</span>` },
            { data: 'available',    name: 'available',
              render: v => `<span class="qty-badge ${v>0?'qty-available':'qty-zero'}">${v}</span>` },
            { data: 'status_badge', name: 'status_badge', orderable: false },
        ],
        drawCallback: function() { updateStats(this.api().rows().data()); }
    });

    // ══ PURCHASES TABLE ══
    var purchasesTable = $('#purchases-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('warehouse.apiPurchases') }}",
        columns: [
            { data: 'id',           name: 'id' },
            { data: 'product_name', name: 'product_name' },
            { data: 'product_code', name: 'product_code' },
            { data: 'product_size', name: 'product_size' },
            { data: 'quantity',     name: 'quantity' },
            { data: 'payment',      name: 'payment',   orderable: false },
            { data: 'price_paid',   name: 'price_paid', orderable: false },
            { data: 'payment',      name: 'payment',   orderable: false },
            { data: 'status_name',  name: 'status_name', orderable: false },
            { data: 'created_at',   name: 'created_at' },
            { data: 'action',       name: 'action',    orderable: false },
        ]
    });

    // ══ TABS ══
    window.switchTab = function(tab, el) {
        $('#tab-stock, #tab-purchases').hide();
        $('#tab-' + tab).show();
        $('.wh-tab').removeClass('active');
        $(el).addClass('active');
        if (tab === 'stock')     stockTable.columns.adjust().draw(false);
        if (tab === 'purchases') purchasesTable.columns.adjust().draw(false);
    };

    // ══ PRODUCT CHANGE — sizes ══
    $('#purchase_product_id').on('change', function() {
        var opt   = $(this).find(':selected');
        var sizes = opt.data('sizes') || '';
        var image = opt.data('image') || '';
        var geo   = opt.data('price-ge') || 0;
        var usa   = opt.data('price-us') || 0;

        // sizes dropdown
        $('#purchase_size').empty().append('<option value="">ზომა</option>');
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $('#purchase_size').append('<option value="' + s + '">' + s + '</option>');
            });
        }

        // ფასები
        $('#purchase_price_geo_text').text(geo);
        $('#purchase_price_georgia_hidden').val(geo);

        // სურათი
        if (image) { $('#purchase_preview').attr('src', image).show(); $('#purchase_no_img').hide(); }
        else        { $('#purchase_preview').hide(); $('#purchase_no_img').show(); }

        // stock info — size ჯერ არ არის არჩეული, ამიტომ ვმალავთ
        $('#current-stock-info').hide();

        calcPurchaseSummary();
    });

    // ══ SIZE CHANGE — stock info ══
    $('#purchase_size').on('change', function() {
        var prodId = $('#purchase_product_id').val();
        var size   = $(this).val();
        if (prodId && size) loadStockInfo(prodId, size);
        else $('#current-stock-info').hide();
    });

    $('#purchase_price_usa_input').on('input', function() {
        $('#purchase_price_usa_hidden').val($(this).val());
        calcPurchaseSummary();
    });

    $('#purchase_qty, #purchase_discount').on('input', calcPurchaseSummary);
    $(document).on('input', '.purchase-payment', calcPurchaseSummary);

    // ══ STOCK INFO ══
    function loadStockInfo(productId, size) {
        $.get("{{ route('warehouse.stockInfo') }}", { product_id: productId, size: size }, function(data) {
            $('#si-physical').text(data.found ? data.physical_qty : 0);
            $('#si-incoming').text(data.found ? data.incoming_qty : 0);
            $('#si-reserved').text(data.found ? data.reserved_qty : 0);
            $('#current-stock-info').show();
        });
    }

    // ══ STAT CARDS ══
    function updateStats(data) {
        var ph=0, inc=0, res=0, low=0;
        data.each(function(row) {
            ph  += parseInt(row.physical_qty) || 0;
            inc += parseInt(row.incoming_qty) || 0;
            res += parseInt(row.reserved_qty) || 0;
            if (parseInt(row.available) <= 3) low++;
        });
        $('#stat-physical').text(ph);
        $('#stat-incoming').text(inc);
        $('#stat-reserved').text(res);
        $('#stat-low').text(low);
    }

    // ══ MODAL OPEN (ახალი) ══
    window.openPurchaseModal = function() {
        $('#purchase_id').val('');
        $('input[name="_method"]', '#form-purchase').val('POST');
        $('#purchase-modal-title').text('📦 ახალი შესყიდვა');
        $('#form-purchase')[0].reset();
        $('.select2-purchase').val(null).trigger('change');
        $('#purchase_preview').hide();
        $('#purchase_no_img').show();
        $('#current-stock-info').hide();
        $('#purchase_size').empty().append('<option value="">ზომა</option>');
        $('#purchase_price_geo_text').text('0');
        $('#purchase_summary_text').html('<span class="text-muted">შეიყვანეთ მონაცემები</span>');
        $('#modal-purchase').modal('show');
    };

    // ══ EDIT ══
    window.editPurchase = function(id) {
        $.get("{{ url('warehouse') }}/" + id + "/edit", function(data) {
            $('#purchase_id').val(data.id);
            $('input[name="_method"]', '#form-purchase').val('PATCH');
            $('#purchase-modal-title').text('✏️ შესყიდვის რედაქტირება #' + data.id);

            // პირველ რიგში sizes dropdown ვავსებთ პირდაპირ,
            // trigger('change') გარეშე — რომ stock info არ დაიბლოკოს
            var opt = $('#purchase_product_id option[value="' + data.product_id + '"]');
            var sizes = opt.data('sizes') || '';

            $('#purchase_size').empty().append('<option value="">ზომა</option>');
            if (sizes) {
                sizes.toString().split(',').forEach(function(s) {
                    s = s.trim();
                    if (s) $('#purchase_size').append('<option value="' + s + '">' + s + '</option>');
                });
            }

            // Select2 — product
            $('#purchase_product_id').val(data.product_id).trigger('change.select2');

            // ფასები და ველები
            var geo = opt.data('price-ge') || data.price_georgia || 0;
            var image = opt.data('image') || '';
            $('#purchase_price_geo_text').text(geo);
            $('#purchase_price_georgia_hidden').val(geo);
            if (image) { $('#purchase_preview').attr('src', image).show(); $('#purchase_no_img').hide(); }
            else        { $('#purchase_preview').hide(); $('#purchase_no_img').show(); }

            // size set + stock info — ერთ ბლოკში, setTimeout გარეშე
            $('#purchase_size').val(data.product_size);
            loadStockInfo(data.product_id, data.product_size);

            // დანარჩენი ველები
            $('#purchase_qty').val(data.quantity);
            $('#purchase_price_usa_input').val(data.price_usa);
            $('#purchase_price_usa_hidden').val(data.price_usa);
            $('#purchase_discount').val(data.discount || 0);
            $('#purchase_status_id').val(data.status_id);
            $('[name="paid_tbc"]', '#form-purchase').val(data.paid_tbc || 0);
            $('[name="paid_bog"]', '#form-purchase').val(data.paid_bog || 0);
            $('[name="paid_lib"]', '#form-purchase').val(data.paid_lib || 0);
            $('[name="paid_cash"]', '#form-purchase').val(data.paid_cash || 0);
            $('#purchase_comment').val(data.comment || '');

            calcPurchaseSummary();
            $('#modal-purchase').modal('show');
        });
    };

    // ══ DELETE ══
    window.deletePurchase = function(id) {
        swal({
            title: 'დარწმუნებული ხარ?', text: 'შესყიდვა წაიშლება!',
            type: 'warning', showCancelButton: true,
            confirmButtonColor: '#dd4b39',
            cancelButtonText: 'გაუქმება', confirmButtonText: 'წაშლა'
        }).then(function() {
            $.ajax({
                url: "{{ url('warehouse') }}/" + id, type: 'POST',
                data: { _method: 'DELETE', _token: "{{ csrf_token() }}" },
                success: function(res) {
                    purchasesTable.ajax.reload();
                    stockTable.ajax.reload();
                    swal({ title: 'წაიშალა!', text: res.message, type: 'success', timer: 1500 });
                }
            });
        });
    };

    // ══ SUBMIT ══
    $('#form-purchase').on('submit', function(e) {
        e.preventDefault();
        $('#purchase_price_usa_hidden').val($('#purchase_price_usa_input').val());

        var id  = $('#purchase_id').val();
        var url = id ? "{{ url('warehouse') }}/" + id : "{{ url('warehouse') }}";

        $.ajax({
            url: url, type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modal-purchase').modal('hide');
                stockTable.ajax.reload();
                purchasesTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 1800 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            }
        });
    });

    // ══ SUMMARY CALC ══
    function calcPurchaseSummary() {
        var usa      = parseFloat($('#purchase_price_usa_input').val()) || 0;
        var qty      = parseInt($('#purchase_qty').val()) || 1;
        var discount = parseFloat($('#purchase_discount').val()) || 0;
        var tbc      = parseFloat($('[name="paid_tbc"]', '#form-purchase').val()) || 0;
        var bog      = parseFloat($('[name="paid_bog"]', '#form-purchase').val()) || 0;
        var lib      = parseFloat($('[name="paid_lib"]', '#form-purchase').val()) || 0;
        var cash     = parseFloat($('[name="paid_cash"]', '#form-purchase').val()) || 0;

        var total = (usa * qty) - discount;
        var paid  = tbc + bog + lib + cash;
        var diff  = total - paid;

        var color = diff > 0.01 ? 'red' : (diff < -0.01 ? 'green' : '#00a65a');
        var label = diff > 0.01
            ? '💳 დავალიანება: $' + diff.toFixed(2)
            : (diff < -0.01 ? '💚 ზედმეტი: $' + Math.abs(diff).toFixed(2) : '✅ სრულად გადახდილია');

        $('#purchase_summary_text').html(
            '<span style="color:' + color + '">' + label + '</span>' +
            ' <span style="color:#888; font-size:11px;">| სულ: $' + total.toFixed(2) + '</span>'
        );
    }

});

// ══ STATUS MODAL ══
window.openStatusModal = function(id, currentStatusId) {
    $('#status_order_id').val(id);
    $('#modal_status_id').val(currentStatusId);
    $('#modal-status').modal('show');
};

function saveStatusUpdate() {
    var id        = $('#status_order_id').val();
    var status_id = $('#modal_status_id').val();

    $.ajax({
        url: "{{ url('warehouse/update-status') }}/" + id,
        type: 'POST',
        data: { _token: "{{ csrf_token() }}", status_id: status_id },
        success: function(res) {
            $('#modal-status').modal('hide');
            $('#purchases-table').DataTable().ajax.reload();
            $('#stock-table').DataTable().ajax.reload();
            swal('შესრულდა', res.message, 'success');
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'შეცდომა სტატუსის განახლებისას';
            swal('შეზღუდვა', msg, 'error');
        }
    });
}
</script>
@endsection