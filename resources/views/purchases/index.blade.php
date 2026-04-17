@extends('layouts.master')
@section('page_title')<i class="fa fa-cart-shopping me-2" style="color:#2980b9;"></i>შესყიდვები@endsection

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
/* Responsive expand control */
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before {
    background-color: #00a65a;
    border-radius: 50%;
}
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
    padding: 14px 20px; border-radius: 6px 6px 0 0;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
}
.wh-header h3 { margin:0; font-size:16px; font-weight:700; }
.wh-header .wh-subtitle { font-size:11px; color:#aaa; margin-top:2px; }
.wh-table thead th {
    background:#f4f4f4; font-size:11px; text-transform:uppercase;
    letter-spacing:0.5px; color:#555;
    border-bottom:2px solid var(--wh-border) !important; white-space:nowrap;
}

/* ── Tab nav customisation ── */
#purchaseTabs .nav-link {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    padding: 9px 18px;
}
#purchaseTabs .nav-link.active {
    color: var(--wh-green);
    border-bottom-color: var(--wh-green);
}
</style>
@endsection

@section('content')
<div class="py-3 px-3 px-md-4">

    <div class="wh-header">
        <div>
            <h3>📦 შესყიდვების ორდერები</h3>
            <div class="wh-subtitle">Purchase Orders Management</div>
        </div>
        <button id="btn-new-purchase" onclick="openPurchaseModal()" class="btn btn-success btn-sm fw-bold">
            <i class="fa fa-plus me-1"></i> ახალი შესყიდვა
        </button>
    </div>

    <div class="mt-3">

        {{-- ══ TABS ══ --}}
        <ul class="nav nav-tabs mb-3" id="purchaseTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-btn-regular"
                        onclick="switchPurchaseTab('regular')" type="button">
                    🛒 შესყიდვები
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-btn-returns"
                        onclick="switchPurchaseTab('returns')" type="button">
                    ↩ დაბრუნება / გაცვლა
                </button>
            </li>
        </ul>

        {{-- ══ ჩვეულებრივი შესყიდვები ══ --}}
        <div id="tab-regular">
            <table id="purchases-table" class="table wh-table table-hover table-bordered w-100">
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

        {{-- ══ დაბრუნება / გაცვლა ══ --}}
        <div id="tab-returns" style="display:none;">
            <table id="returns-table" class="table wh-table table-hover table-bordered w-100">
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

</div>

{{-- Status Modal --}}
<div class="modal fade" id="modal-status" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">სტატუსის შეცვლა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_order_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">ახალი სტატუსი</label>
                    <select id="new_status_id" class="form-select">
                        @foreach($statuses as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-primary" onclick="submitStatus()">შენახვა</button>
            </div>
        </div>
    </div>
</div>

{{-- Partial Receive Modal --}}
<div class="modal fade" id="modal-partial-receive" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#f39c12; color:#fff; border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📦 საწყობში მიღება</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="partial_purchase_id">

                {{-- პროდუქტის ინფო --}}
                <div class="p-3 mb-3 rounded border bg-light" style="font-size:13px;">
                    <div><strong id="partial_product_name">—</strong></div>
                    <div class="mt-1 text-muted">
                        შეკვეთილი: <strong id="partial_total_qty">—</strong> ერთ.
                        &nbsp;|&nbsp; ზომა: <strong id="partial_size">—</strong>
                    </div>
                </div>

                {{-- სამი ველი გვერდიგვერდ --}}
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold text-success" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">✅ მიღებული</label>
                        <input type="number" id="partial_received_qty" class="form-control text-center fw-bold partial-qty-input"
                               min="0" value="0" style="font-size:20px; border-color:#00a65a; border-width:2px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold text-danger" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">❌ დაკარგ.</label>
                        <input type="number" id="partial_lost_qty" class="form-control text-center fw-bold partial-qty-input"
                               min="0" value="0" style="font-size:20px; border-color:#dd4b39; border-width:2px;">
                    </div>
                </div>

                {{-- შენიშვნები (ჩნდება თუ > 0) --}}
                <div id="partial_lost_note_wrap" style="display:none;" class="mb-2">
                    <input type="text" id="partial_lost_note" class="form-control form-control-sm"
                           placeholder="❌ დაკარგულის შენიშვნა (სურვილისამებრ)">
                </div>

                {{-- Summary --}}
                <div class="p-3 rounded bg-light" style="font-size:13px;">
                    ჯამი: <strong id="partial_sum_display">0</strong>
                    / <strong id="partial_total_display">—</strong> ერთ.
                    &nbsp;&nbsp;
                    <span id="partial_remaining_text" class="fw-bold"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success" onclick="submitPartialReceive()" id="btn-partial-save">
                    <i class="fa fa-check me-1"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

@include('purchases.form_purchase')

{{-- ══ Group View Modal ══ --}}
<div class="modal fade" id="modal-group-view" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold">📋 ჯგუფის შემადგენლობა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive" id="gv-body"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Group Receive Modal ══ --}}
<div class="modal fade" id="modal-group-receive" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#f39c12;color:#fff;border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📦 საწყობში მიღება</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="gr-group-id">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>პროდუქტი</th>
                                <th style="width:80px">ზომა</th>
                                <th style="width:75px" class="text-center">შეკვ.</th>
                                <th style="width:100px">
                                    <span class="text-success">✅ მიღებული</span>
                                </th>
                                <th style="width:100px">
                                    <span class="text-danger">❌ დაკარგ.</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="gr-lines-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success" id="btn-gr-save" onclick="submitGroupReceive()">
                    <i class="fa fa-check me-1"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(function() {

    // ══ TAB SWITCHING ══
    var currentTab = 'regular';

    window.switchPurchaseTab = function(tab) {
        currentTab = tab;

        // Bootstrap 5 nav-link active classes
        $('#tab-btn-regular, #tab-btn-returns').removeClass('active');
        $('#tab-btn-' + tab).addClass('active');

        // tab content
        $('#tab-regular, #tab-returns').hide();
        $('#tab-' + tab).show();

        // "ახალი შესყიდვა" ღილაკი მხოლოდ regular tab-ზე
        $('#btn-new-purchase').toggle(tab === 'regular');

        if (tab === 'regular') {
            purchasesTable.columns.adjust().draw(false);
        } else {
            returnsTable.columns.adjust().draw(false);
        }
    };

    // ══ PURCHASES TABLE (ჩვეულებრივი) ══
    var purchasesTable = $('#purchases-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: { type: 'regular' }
        },
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2 },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 1, orderable: false },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9 },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 3 },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 4 },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7 },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8 },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 5 },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 6 },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2 },
            { data: 'is_return_purchase', visible: false },
            { data: 'group_items_json',   visible: false },
        ]
    });

    // ══ GROUP VIEW ══
    window.openGroupView = function(groupId) {
        $.get("{{ url('purchases/group') }}/" + groupId + "/items", function(items) {
            var html = '<table class="table table-sm table-bordered mb-0">'
                     + '<thead class="table-light"><tr><th>პროდუქტი</th><th>კოდი</th><th>ზომა</th><th class="text-center">რაოდ.</th><th>სტატუსი</th></tr></thead><tbody>';
            (items || []).forEach(function(it) {
                html += '<tr><td class="fw-semibold">' + (it.product_name||'N/A') + '</td>'
                     +  '<td class="text-muted" style="font-size:12px;">' + (it.product_code||'—') + '</td>'
                     +  '<td>' + (it.product_size||'—') + '</td>'
                     +  '<td class="text-center fw-bold">' + (it.quantity||0) + '</td>'
                     +  '<td><span class="label label-'+(it.status_color||'default')+'" style="font-size:11px;">' + (it.status_name||'-') + '</span></td></tr>';
            });
            html += '</tbody></table>';
            $('#gv-body').html(html);
            new bootstrap.Modal(document.getElementById('modal-group-view')).show();
        });
    };

    // ══ RETURNS TABLE (დაბრუნება / გაცვლა) ══
    var returnsTable = $('#returns-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('purchases.api') }}",
            data: { type: 'returns' }
        },
        columns: [
            { data: 'order_number',    name: 'order_number',    responsivePriority: 2 },
            { data: 'product_name',    name: 'product_name',    responsivePriority: 1, orderable: false },
            { data: 'product_code',    name: 'product_code',    responsivePriority: 9 },
            { data: 'product_size',    name: 'product_size',    responsivePriority: 3 },
            { data: 'quantity',        name: 'quantity',        responsivePriority: 4 },
            { data: 'payment',         name: 'payment',         orderable: false, responsivePriority: 7 },
            { data: 'price_paid',      name: 'price_paid',      orderable: false, responsivePriority: 8 },
            { data: 'status_name',     name: 'status_name',     orderable: false, responsivePriority: 5 },
            { data: 'created_at',      name: 'created_at',      responsivePriority: 6 },
            { data: 'action',          name: 'action',          orderable: false, responsivePriority: 2 },
            { data: 'is_return_purchase', visible: false },
            { data: 'group_items_json',   visible: false },
        ],
        createdRow: function(row) {
            $(row).css('background-color', '#d9edf7');
        }
    });

    // ══ MULTI-LINE PURCHASE FORM ══
    var purchaseLineIndex = 0;
    var productOptionsTpl = document.getElementById('tpl-product-options').innerHTML;

    window.addPurchaseLine = function(defaults) {
        var idx = purchaseLineIndex++;

        var $prodSel = $('<select required>')
            .addClass('form-select form-select-sm line-product w-100')
            .attr('name', 'items[' + idx + '][product_id]')
            .html(productOptionsTpl);

        var $sizeSel = $('<select required>')
            .addClass('form-select form-select-sm line-size')
            .attr('name', 'items[' + idx + '][product_size]')
            .append('<option value="">—</option>');

        var $qty = $('<input type="number" required>')
            .addClass('form-control form-control-sm line-qty')
            .attr({ name: 'items[' + idx + '][quantity]', min: 1, value: 1 });

        var $priceUsa = $('<input type="number">')
            .addClass('form-control form-control-sm line-price-usa')
            .attr({ name: 'items[' + idx + '][price_usa]', step: '0.01', min: 0, placeholder: '0.00' });

        var $transport = $('<input type="number">')
            .addClass('form-control form-control-sm line-transport')
            .attr({ name: 'items[' + idx + '][transport]', step: '0.01', min: 0, placeholder: '0.00', value: 0 });

        var $priceGeo = $('<input type="text" readonly>')
            .addClass('form-control form-control-sm line-price-geo bg-light')
            .attr('name', 'items[' + idx + '][price_georgia]')
            .attr('placeholder', '0.00');

        var $fifo = $('<small class="line-fifo">');

        var $removeBtn = $('<button type="button">')
            .addClass('btn btn-outline-danger btn-sm remove-line p-1')
            .html('<i class="fa fa-times"></i>');

        var $tr = $('<tr class="purchase-line">').append(
            $('<td>').append($prodSel),
            $('<td>').append($sizeSel),
            $('<td>').append($qty),
            $('<td>').append($priceUsa).append($fifo),
            $('<td>').append($transport),
            $('<td>').append($priceGeo),
            $('<td class="text-center">').append($removeBtn)
        );

        if (defaults) {
            $prodSel.val(defaults.product_id || '');
            var sizes = ($prodSel.find(':selected').data('sizes') || '').toString();
            if (sizes) {
                sizes.split(',').forEach(function(s) {
                    s = s.trim();
                    if (s) $sizeSel.append('<option value="' + s + '">' + s + '</option>');
                });
            }
            $sizeSel.val(defaults.product_size || '');
            $qty.val(defaults.quantity || 1);
            $priceUsa.val(defaults.price_usa || '');
            $transport.val(defaults.transport != null ? defaults.transport : 0);
            $priceGeo.val(defaults.price_georgia ? parseFloat(defaults.price_georgia).toFixed(2) : '');
        }

        $('#purchase-lines-body').append($tr);
        updateRemoveButtons();
        calcPurchaseSummary();
    };

    function updateRemoveButtons() {
        var $rows = $('#purchase-lines-body .purchase-line');
        $rows.find('.remove-line').toggle($rows.length > 1);
    }

    // ── line events (delegated) ──
    $(document).on('change', '#purchase-lines-body .line-product', function() {
        var $tr    = $(this).closest('tr');
        var opt    = $(this).find(':selected');
        var sizes  = (opt.data('sizes') || '').toString();
        var geo    = opt.data('price-ge') || 0;

        var $sz = $tr.find('.line-size').empty().append('<option value="">—</option>');
        if (sizes) {
            sizes.split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sz.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $tr.find('.line-price-geo').val(geo ? parseFloat(geo).toFixed(2) : '');
        $tr.find('.line-fifo').text('');
        calcPurchaseSummary();
    });

    $(document).on('change', '#purchase-lines-body .line-size', function() {
        var $tr    = $(this).closest('tr');
        var prodId = $tr.find('.line-product').val();
        var size   = $(this).val();
        if (prodId && size) {
            $.get("{{ route('warehouse.stockInfo') }}", { product_id: prodId, size: size }, function(d) {
                $tr.find('.line-fifo').text(d.fifo_cost ? 'FIFO: $' + d.fifo_cost : '');
            });
        }
    });

    $(document).on('input', '#purchase-lines-body .line-price-usa, #purchase-lines-body .line-transport, #purchase-lines-body .line-qty', calcPurchaseSummary);

    $(document).on('input', '.purchase-payment', calcPurchaseSummary);

    $('#purchase_discount').on('input', calcPurchaseSummary);

    $(document).on('click', '#purchase-lines-body .remove-line', function() {
        $(this).closest('tr').remove();
        updateRemoveButtons();
        calcPurchaseSummary();
    });

    // ══ SUMMARY CALC ══
    function calcPurchaseSummary() {
        var total = 0;
        $('#purchase-lines-body .purchase-line').each(function() {
            var usa  = parseFloat($(this).find('.line-price-usa').val())  || 0;
            var tr   = parseFloat($(this).find('.line-transport').val())  || 0;
            var qty  = parseInt($(this).find('.line-qty').val())          || 1;
            total += (usa + tr) * qty;
        });
        var discount = parseFloat($('#purchase_discount').val()) || 0;
        total -= discount;

        var tbc  = parseFloat($('[name="paid_tbc"]',  '#form-purchase').val()) || 0;
        var bog  = parseFloat($('[name="paid_bog"]',  '#form-purchase').val()) || 0;
        var lib  = parseFloat($('[name="paid_lib"]',  '#form-purchase').val()) || 0;
        var cash = parseFloat($('[name="paid_cash"]', '#form-purchase').val()) || 0;
        var paid = tbc + bog + lib + cash;
        var diff = total - paid;

        var color = diff > 0.01 ? 'red' : (diff < -0.01 ? 'green' : '#00a65a');
        var label = diff > 0.01
            ? '💳 დავალიანება: $' + diff.toFixed(2)
            : (diff < -0.01 ? '💚 ზედმეტი: $' + Math.abs(diff).toFixed(2) : '✅ გადახდილია');

        $('#purchase_summary_text').text(label).css('color', color);
    }

    // ══ MODAL OPEN ══
    window.openPurchaseModal = function() {
        purchaseLineIndex = 0;
        $('#purchase_id').val('');
        $('input[name="_method"]', '#form-purchase').val('POST');
        $('#purchase-modal-title').text('📦 ახალი შესყიდვა');
        $('#purchase-lines-body').empty();
        $('[name="paid_tbc"],[name="paid_bog"],[name="paid_lib"],[name="paid_cash"]', '#form-purchase').val(0);
        $('#purchase_discount').val(0);
        $('#purchase_comment').val('');
        $('#purchase_courier_section').hide();
        $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
        $('#btn-add-line').show();
        addPurchaseLine();
        $('#purchase_summary_text').html('<span class="text-muted">შეიყვანეთ მონაცემები</span>');
        $('#modal-purchase').modal('show');
    };

    // ══ EDIT ══
    window.editPurchase = function(id) {
        $.get("{{ url('purchases') }}/" + id + "/edit", function(data) {
            purchaseLineIndex = 0;
            $('#purchase_id').val(data.id);
            $('input[name="_method"]', '#form-purchase').val('PATCH');
            $('#purchase-modal-title').text('✏️ ' + (data.order_number || '#' + data.id));
            $('#purchase-lines-body').empty();
            $('#btn-add-line').hide();

            $('[name="paid_tbc"]',  '#form-purchase').val(data.paid_tbc  || 0);
            $('[name="paid_bog"]',  '#form-purchase').val(data.paid_bog  || 0);
            $('[name="paid_lib"]',  '#form-purchase').val(data.paid_lib  || 0);
            $('[name="paid_cash"]', '#form-purchase').val(data.paid_cash || 0);
            $('#purchase_discount').val(data.discount || 0);
            $('#purchase_comment').val(data.comment   || '');

            // courier section for return/exchange
            if (data.is_return_purchase) {
                $('#purchase_courier_section').show();
                var cType = 'none';
                if ((data.courier_price_tbilisi || 0) > 0) cType = 'tbilisi';
                else if ((data.courier_price_region  || 0) > 0) cType = 'region';
                else if ((data.courier_price_village || 0) > 0) cType = 'village';
                $('input[name="purchase_courier_type"][value="' + cType + '"]').prop('checked', true);
            } else {
                $('#purchase_courier_section').hide();
                $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
            }

            addPurchaseLine({
                product_id:   data.product_id,
                product_size: data.product_size,
                quantity:     data.quantity,
                price_usa:    data.price_usa,
                transport:    data.is_return_purchase ? 0 : (data.courier_price_international || 0),
                price_georgia: data.price_georgia || 0,
            });

            // lock if sales already dispatched
            var courierCount = data.courier_count || 0;
            if (courierCount > 0) {
                var $tr = $('#purchase-lines-body .purchase-line');
                $tr.find('.line-product, .line-size, .line-price-usa, .line-transport')
                   .prop('disabled', true).css('background', '#f5f5f5');
                $tr.find('.line-qty').attr('min', courierCount).css('background', '#fff8e1');
                $('#purchase-courier-lock-msg').remove();
                var lockMsg = '<div id="purchase-courier-lock-msg" style="background:#fff3cd;border:1px solid #ffc107;' +
                    'border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:12px;color:#856404;">' +
                    '⚠️ <strong>' + courierCount + ' ერთეული</strong> გაყიდულია — პროდუქტი/ზომა/ფასი/ტრანსპ. ვერ შეიცვლება.</div>';
                $('#form-purchase .modal-body').prepend(lockMsg);
            }

            calcPurchaseSummary();
            $('#modal-purchase').modal('show');
        });
    };

    $('#modal-purchase').on('hidden.bs.modal', function() {
        $('#purchase-lines-body .line-product, #purchase-lines-body .line-size, ' +
          '#purchase-lines-body .line-price-usa, #purchase-lines-body .line-transport')
            .prop('disabled', false).css('background', '');
        $('#purchase-courier-lock-msg').remove();
        $('#btn-add-line').show();
        $('#purchase_courier_section').hide();
        $('input[name="purchase_courier_type"][value="none"]').prop('checked', true);
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
                    returnsTable.ajax.reload();
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
        var id  = $('#purchase_id').val();
        var url = id ? "{{ url('purchases') }}/" + id : "{{ url('purchases') }}";

        var $locked = $(this).find(':disabled').prop('disabled', false);
        var formData;

        if (id) {
            // EDIT — send flat fields that update() expects
            var $tr = $('#purchase-lines-body .purchase-line').first();
            formData = {
                _method:                     'PATCH',
                _token:                      "{{ csrf_token() }}",
                order_type:                  'purchase',
                product_id:                  $tr.find('.line-product').val(),
                product_size:                $tr.find('.line-size').val(),
                quantity:                    $tr.find('.line-qty').val(),
                price_usa:                   $tr.find('.line-price-usa').val() || 0,
                courier_price_international: $tr.find('.line-transport').val() || 0,
                price_georgia:               $tr.find('.line-price-geo').val() || 0,
                purchase_courier_type:       $('input[name="purchase_courier_type"]:checked').val() || 'none',
                discount:                    $('#purchase_discount').val() || 0,
                paid_tbc:                    $('[name="paid_tbc"]',  this).val() || 0,
                paid_bog:                    $('[name="paid_bog"]',  this).val() || 0,
                paid_lib:                    $('[name="paid_lib"]',  this).val() || 0,
                paid_cash:                   $('[name="paid_cash"]', this).val() || 0,
                comment:                     $('#purchase_comment').val(),
            };
        } else {
            formData = $(this).serialize();
        }

        $locked.prop('disabled', true);

        $.ajax({
            url: url, type: 'POST',
            data: formData,
            success: function(res) {
                $('#modal-purchase').modal('hide');
                purchasesTable.ajax.reload();
                returnsTable.ajax.reload();
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
                var total = data.quantity;
                $('#partial_purchase_id').val(orderId);
                $('#partial_product_name').text(data.product_name || 'Purchase #' + orderId);
                $('#partial_size').text(data.product_size || '');
                $('#partial_total_qty').text(total);
                $('#partial_total_display').text(total);
                // ველების reset
                $('#partial_received_qty').val(total);
                $('#partial_lost_qty').val(0);
                $('#partial_lost_note').val('');
                $('#partial_lost_note_wrap').hide();
                updatePartialSummary();
                new bootstrap.Modal(document.getElementById('modal-partial-receive')).show();
            } else {
                $('#status_order_id').val(orderId);
                $('#new_status_id').val(currentStatus);
                new bootstrap.Modal(document.getElementById('modal-status')).show();
            }
        });
    };

    // ══ PARTIAL RECEIVE — input listeners ══
    $(document).on('input', '.partial-qty-input', function() {
        updatePartialSummary();
        $('#partial_lost_note_wrap').toggle((parseInt($('#partial_lost_qty').val()) || 0) > 0);
    });

    function updatePartialSummary() {
        var total   = parseInt($('#partial_total_qty').text()) || 0;
        var got     = parseInt($('#partial_received_qty').val()) || 0;
        var lost    = parseInt($('#partial_lost_qty').val())    || 0;
        var sum     = got + lost;
        var rem     = total - sum;

        $('#partial_sum_display').text(sum);

        var txt = '';
        if (sum > total) {
            txt = '<span style="color:#dd4b39;">❌ ჯამი აღემატება შეკვეთილს!</span>';
        } else if (rem === 0 && got > 0) {
            txt = '<span style="color:#00a65a;">✅ სრულად დათვლილია</span>';
        } else if (rem === 0 && got === 0) {
            txt = '<span style="color:#f39c12;">⚠️ ყველა დაკარგულია</span>';
        } else if (rem > 0) {
            txt = '<span style="color:#f39c12;">⚠️ ' + rem + ' ერთ. კვლავ გზაში დარჩება</span>';
        }
        $('#partial_remaining_text').html(txt);
    }

    window.submitPartialReceive = function() {
        var id     = $('#partial_purchase_id').val();
        var got    = parseInt($('#partial_received_qty').val()) || 0;
        var lost   = parseInt($('#partial_lost_qty').val())     || 0;
        var max    = parseInt($('#partial_total_qty').text())   || 0;
        var sum    = got + lost;

        if (sum < 1)    { swal('შეცდომა', 'შეიყვანეთ მინიმუმ 1 ერთეული', 'error'); return; }
        if (sum > max)  { swal('შეცდომა', 'ჯამი (' + sum + ') აღემატება შეკვეთილ რაოდენობას (' + max + ')', 'error'); return; }

        $('#btn-partial-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ url('purchases') }}/" + id + "/partial-receive",
            type: 'POST',
            data: {
                received_qty: got,
                defect_qty:   0,
                lost_qty:     lost,
                lost_note:    $('#partial_lost_note').val()   || '',
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-partial-receive')).hide();
                purchasesTable.ajax.reload();
                returnsTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 2500 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-partial-save').prop('disabled', false).html('<i class="fa fa-check me-1"></i> დადასტურება');
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
                bootstrap.Modal.getInstance(document.getElementById('modal-status')).hide();
                purchasesTable.ajax.reload();
                returnsTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 1500 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            }
        });
    };

    // ══ GROUP RECEIVE ══
    window.openGroupReceive = function(groupId) {
        $('#gr-lines-body').empty();
        $('#gr-group-id').val(groupId);
        $('#btn-gr-save').prop('disabled', false);

        $.get("{{ url('purchases/group') }}/" + groupId + "/items", function(items) {
            if (!items || !items.length) {
                swal('ინფო', 'ამ ჯგუფში სტატუს=2 ორდერი არ მოიძებნა', 'info');
                return;
            }
            items.forEach(function(it) {
                var $tr = $('<tr data-order-id="' + it.id + '">').append(
                    $('<td class="fw-semibold">').text(it.product_name),
                    $('<td>').text(it.product_size || '—'),
                    $('<td class="text-center fw-bold text-muted gr-ordered">').text(it.quantity),
                    $('<td>').append(
                        $('<input type="number" class="form-control form-control-sm text-center gr-received">')
                            .val(it.quantity).attr({ min: 0, max: it.quantity })
                    ),
                    $('<td>').append(
                        $('<input type="number" class="form-control form-control-sm text-center gr-lost">')
                            .val(0).attr({ min: 0, max: it.quantity })
                    )
                );
                $('#gr-lines-body').append($tr);
            });
            new bootstrap.Modal(document.getElementById('modal-group-receive')).show();
        });
    };

    // validation: received + lost <= ordered
    $(document).on('input', '.gr-received, .gr-lost', function() {
        var $tr      = $(this).closest('tr');
        var ordered  = parseInt($tr.find('.gr-ordered').text()) || 0;
        var received = parseInt($tr.find('.gr-received').val()) || 0;
        var lost     = parseInt($tr.find('.gr-lost').val())     || 0;
        if (received + lost > ordered) {
            $(this).addClass('is-invalid');
        } else {
            $tr.find('.gr-received, .gr-lost').removeClass('is-invalid');
        }
    });

    window.submitGroupReceive = function() {
        var groupId = $('#gr-group-id').val();
        var items = [];
        var valid = true;

        $('#gr-lines-body tr').each(function() {
            var orderId  = $(this).data('order-id');
            var received = parseInt($(this).find('.gr-received').val()) || 0;
            var lost     = parseInt($(this).find('.gr-lost').val())     || 0;
            var ordered  = parseInt($(this).find('.gr-ordered').text()) || 0;

            if (received + lost > ordered) { valid = false; }
            items.push({ order_id: orderId, received_qty: received, lost_qty: lost });
        });

        if (!valid) { swal('შეცდომა', 'ერთ-ერთი ხაზის ჯამი აღემატება შეკვეთილ რაოდენობას', 'error'); return; }

        $('#btn-gr-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ url('purchases/group') }}/" + groupId + "/partial-receive",
            type: 'POST',
            data: { items: items, _token: "{{ csrf_token() }}" },
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-group-receive')).hide();
                purchasesTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success' });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-gr-save').prop('disabled', false).html('<i class="fa fa-check me-1"></i> დადასტურება');
            }
        });
    };

});
</script>
@endsection
