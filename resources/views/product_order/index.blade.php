@extends('layouts.master')

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
<style>
.select2-container--default .select2-selection--single {
    height: 34px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 34px;
    padding-left: 8px;
    color: #333;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #999;
}
</style>
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
        <th style="width:110px;">№ / <input type="checkbox" id="check-all" title="ყველას მონიშვნა"></th>
        <th style="width:40px;"></th>  {{-- expand ღილაკი --}}
        <th style="width:80px;">თარიღი</th>
        <th>სტატუსი</th>
        <th style="width:70px;">Picture</th>
        <th>Product</th>
        <th>Customer</th>
        <th>Payment</th>
        <th style="display:none;"></th> {{-- cross_ref_html hidden --}}
        <th style="display:none;"></th> {{-- has_mergeable hidden --}}
        <th style="display:none;"></th> {{-- children_by_status hidden --}}
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

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- 🔄 Change Order Modal                                       --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-change" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius:8px;">
            <form id="form-change">
                @csrf
                <input type="hidden" name="original_sale_id" id="change_original_sale_id">

                <div class="modal-header" style="background:#f39c12; color:#fff; border-radius:8px 8px 0 0;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:1;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> გაცვლა / დაბრუნება</h4>
                </div>

                <div class="modal-body">

                    {{-- original info --}}
                    <div style="background:#f9f9f9; border:1px solid #ddd; border-radius:6px;
                                padding:10px 14px; margin-bottom:14px; font-size:13px;">
                        <i class="fa fa-cube" style="color:#888;"></i>
                        <strong id="change-orig-product">—</strong>
                        <span id="change-orig-size" class="label label-info" style="margin-left:6px;"></span>
                        <span style="color:#888; margin-left:8px;">Sale #<span id="change-orig-id">—</span></span>
                    </div>

                    {{-- ტიპი --}}
                    <div class="form-group">
                        <label style="font-weight:600;">ტიპი</label>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="return" checked> ↩ დაბრუნება
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="size"> 📐 ზომის გაცვლა
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="change_type" value="product"> 🔄 პროდუქტის გაცვლა
                            </label>
                        </div>
                    </div>

                    {{-- პროდუქტი / ზომა --}}
                    <div id="change-new-fields">
                        <div class="row">
                            <div class="col-md-7" id="change-product-group" style="display:none;">
                                <div class="form-group">
                                    <label style="font-weight:600;">ახალი პროდუქტი</label>
                                    <select name="product_id" id="change_product_id" class="form-control" required>
                                        <option value="">— აირჩიე —</option>
                                        @foreach($all_products as $product)
                                            <option value="{{ $product->id }}"
                                                data-sizes="{{ $product->sizes }}"
                                                data-price-ge="{{ $product->price_geo }}">
                                                {{ $product->name }}
                                                @if($product->product_code)({{ $product->product_code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label style="font-weight:600;">ახალი ზომა</label>
                                    <select name="product_size" id="change_size" class="form-control" required>
                                        <option value="">— ზომა —</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Stock info --}}
                        <div id="change-stock-info" style="display:none; background:#f4f4f4;
                             border-radius:8px; padding:10px 14px; margin-bottom:10px;">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase;
                                        color:#888; margin-bottom:6px;">მიმდინარე ნაშთი</div>
                            <div class="row text-center">
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#3c763d;" id="chg-si-physical">0</div>
                                    <div style="font-size:10px; color:#888;">📦 ფიზიკური</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#31708f;" id="chg-si-incoming">0</div>
                                    <div style="font-size:10px; color:#888;">🚚 გზაში</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800; color:#8a6d3b;" id="chg-si-reserved">0</div>
                                    <div style="font-size:10px; color:#888;">🔒 დაჯავშნ.</div>
                                </div>
                                <div class="col-xs-3">
                                    <div style="font-size:20px; font-weight:800;" id="chg-si-available">0</div>
                                    <div style="font-size:10px; color:#888;">✅ თავისუფალი</div>
                                </div>
                            </div>
                            <div style="margin-top:8px; text-align:center;" id="chg-si-badge"></div>
                        </div>

                        {{-- ფასთა სხვაობა --}}
                        <div id="change-price-diff-block" style="display:none; background:#fff8e1;
                             border:1px solid #ffe082; border-radius:6px; padding:10px 14px;
                             margin-bottom:10px; font-size:13px;">
                            <span style="color:#888;">ფასთა სხვაობა:</span>
                            <strong id="change-price-diff" style="font-size:15px; margin-left:6px;">—</strong>
                        </div>
                    </div>

                    {{-- გადახდა --}}
                    <div class="well well-sm" style="background:#f4f4f4; border:1px solid #ddd; padding:10px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            <i class="fa fa-credit-card"></i> გადახდა (სხვაობა)
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon">TBC</span>
                            <input type="number" name="paid_tbc" class="form-control" placeholder="0" step="0.01" value="0">
                            <span class="input-group-addon">BOG</span>
                            <input type="number" name="paid_bog" class="form-control" placeholder="0" step="0.01" value="0">
                            <span class="input-group-addon">Cash</span>
                            <input type="number" name="paid_cash" class="form-control" placeholder="0" step="0.01" value="0">
                        </div>
                    </div>

                    {{-- შენიშვნა --}}
                    <div class="form-group" style="margin-top:10px;">
                        <label style="font-weight:600;">შენიშვნა</label>
                        <textarea name="comment" id="change_comment" class="form-control" rows="2"
                                  placeholder="შენიშვნა..."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-refresh"></i> დარეგისტრირება
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    // სვეტი 1: ორდერის ნომერი + checkbox
    // სვეტი 1: ორდერის ნომერი, შემდეგ checkbox + 📦 ერთ ხაზზე
    {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data) {
            var orderNo = data.order_number || ('S' + data.id);

            // 1. ჩეკბოქსი და ნომერი
            var cb = (data.merged_id && !data.is_primary)
                ? ''
                : '<input type="checkbox" class="row-check" data-id="' + data.id + '" data-status="' + data.status_id + '" style="margin:0;">';

            // 🔍 გასაერთიანებელი ორდერების მინიშნება
            var mergeHint = '';
            if (data.has_mergeable && data.customer_id && data.status !== 'deleted') {
                mergeHint = '&nbsp;<span class="merge-search-btn" data-customer-id="' + data.customer_id + '" ' +
                    'title="ამ კლიენტის სხვა ორდერებიც არსებობს — დააჭირეთ გასაფილტრად და მოსანიშნად" ' +
                    'style="cursor:pointer; color:#e67e22;">' +
                    '<i class="fa fa-search"></i></span>';
            }

            var headerRow = '<div style="display:flex; align-items:center; gap:5px;">' +
                                cb +
                                '<small style="font-weight:600; color:#333; white-space:nowrap;">' + orderNo + mergeHint + '</small>' +
                            '</div>';

            // 2. cross-reference ბეჯი (გაცვლა / დაბრუნება)
            var crossRef = (data.cross_ref_html && data.cross_ref_html.length > 0)
                ? '<div style="margin-top:2px;">' + data.cross_ref_html + '</div>'
                : '';

            // 3. Expand ბეჯები სტატუსების მიხედვით + ცალკე expand ღილაკი
            var expandSection = '';
            if (data.is_primary && data.children_count > 0) {
                window._childrenStore = window._childrenStore || {};
                window._childrenStore[data.id] = Array.isArray(data.children_json)
                    ? data.children_json
                    : (typeof data.children_json === 'string' ? JSON.parse(data.children_json || '[]') : []);

                var groups = Array.isArray(data.children_by_status)
                    ? data.children_by_status
                    : (typeof data.children_by_status === 'string' ? JSON.parse(data.children_by_status || '[]') : []);

                var badgesHtml = '';
                groups.forEach(function(g) {
                    badgesHtml +=
                        '<span class="label label-' + g.color + '" ' +
                        'style="font-size:11px; display:inline-block; margin-right:3px;">' +
                        '<i class="fa fa-cube" style="font-size:9px; margin-right:2px;"></i>' + g.count +
                        '</span>';
                });

                expandSection =
                    '<div style="margin-top:4px; display:flex; align-items:center; gap:4px;">' +
                        badgesHtml +
                        '<span class="expand-btn" data-id="' + data.id + '" ' +
                        'style="cursor:pointer; color:#999; font-size:11px; margin-left:2px;">' +
                        '<i class="fa fa-chevron-right"></i>' +
                        '</span>' +
                    '</div>';
            } else if (!data.is_primary && data.merged_id) {
                expandSection = '<div style="margin-top:4px; padding-left:5px; color:#bbb;"><i class="fa fa-level-up fa-rotate-90"></i></div>';
            }

            return '<div style="display:flex; flex-direction:column; justify-content:center;">' + 
                        headerRow + 
                        crossRef +
                        expandSection + 
                   '</div>';
        }
    },
    // სვეტი 3: თარიღი
    {data: 'created_at', name: 'created_at',
        render: function(data) {
            if (data) {
                let d = new Date(data);
                return ("0"+d.getDate()).slice(-2) + '.' + ("0"+(d.getMonth()+1)).slice(-2) + '.' + d.getFullYear();
            }
            return '';
        }
    },
    // სვეტი 4: სტატუსი
    {data: 'status_label', name: 'status_label', orderable: false, searchable: false},
    // სვეტი 5: Picture
    {data: 'show_photo',   name: 'show_photo',   orderable: false, searchable: false},
    // სვეტი 6: Product
    {data: 'product_info', name: 'product_info', orderable: false, searchable: false},
    // სვეტი 7: Customer + Contact
    {data: 'customer_name', name: 'customer_name'},
    // სვეტი 8: Payment + Prices
    {data: 'payment',      name: 'payment',      orderable: false, searchable: false},
    // hidden: cross-reference (გაცვლა/დაბრუნების ბმული) — render-ში გამოიყენება
    {data: 'cross_ref_html', name: 'cross_ref_html', orderable: false, searchable: false, visible: false},
    // hidden: გასაერთიანებელი customer flag
    {data: 'has_mergeable', name: 'has_mergeable', orderable: false, searchable: false, visible: false},
    // hidden: children სტატუსებით
    {data: 'children_by_status', name: 'children_by_status', orderable: false, searchable: false, visible: false},
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
    order: [[2, 'desc']],
    createdRow: function(row, data) {
        // გაცვლილი (status=6) — ღია მოიისფერი ფონი
        if (data.status_id == 6) {
            $(row).css('background-color', '#f5eef8');
            return;
        }
        // დაბრუნებული (status=5) — ღია ნაცრისფერი ფონი
        if (data.status_id == 5) {
            $(row).css('background-color', '#f2f3f4');
            return;
        }
        // change ორდერი — ლურჯი ფონი
        if (data.original_sale_id) {
            $(row).css('background-color', '#d9edf7');
            return;
        }
        // დავალიანება — წითელი ფონი
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
// =====================
// Select2 — product (sale)
// =====================
$('#product_id_sale').select2({
    dropdownParent: $('#modal-sale'),
    placeholder: '— Choose Product —',
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
             isEditMode = false;
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
     isEditMode = true; // ← დაამატე თავში
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

            // კურიერი — customer trigger-ის შემდეგ ვაყენებთ რომ გადაეწეროს
            setTimeout(function() {
                var courierVal = data.courier_servise_local || 'none';
                $('input[name="courier_type"][value="' + courierVal + '"]').prop('checked', true);
            }, 50);

           // 🔒 პროდუქტის და ზომის ბლოკირების ლოგიკა
var statusId = data.status_id ? parseInt(data.status_id) : 1;
var pSelect = $('#product_id_sale');
var sSelect = $('#size_sale');

// ყოველთვის გავხსნათ პირველ რიგში
pSelect.prop('disabled', false);
sSelect.prop('disabled', false);
$('.edit-lock-msg').remove();

// status=4 (კურიერთან) — პროდუქტი და ზომა ჩაკეტილი
if (statusId === 4) {
    pSelect.prop('disabled', true);
    sSelect.prop('disabled', true);
    pSelect.closest('.form-group').find('label').first()
        .append(' <span class="edit-lock-msg label label-warning" style="font-size:10px;">🔒 კურიერთანაა</span>');
    sSelect.closest('.form-group').find('label').first()
        .append(' <span class="edit-lock-msg label label-warning" style="font-size:10px;">🔒</span>');
}

            // 4. ბანკები და ფასდაკლება
            $('#modal-sale input[name="paid_tbc"]').val(data.paid_tbc || 0);
            $('#modal-sale input[name="paid_bog"]').val(data.paid_bog || 0);
            $('#modal-sale input[name="paid_lib"]').val(data.paid_lib || 0);
            $('#modal-sale input[name="paid_cash"]').val(data.paid_cash || 0);
            $('#discount_sale').val(data.discount || 0);

            // 5. კურიერის ლოგიკა — customer trigger-ის შემდეგ გადაეწეროს
            var courierVal = data.courier_servise_local || 'none';

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
        clearInterval(checkSizeExist);

        // isEditMode დარწმუნებით true-ია ზომის დაყენებამდე
        isEditMode = true;
        $('#size_sale').val(data.product_size);

        // display ველები პირდაპირ ჩავავსოთ — FIFO-ს არ ველოდოთ
        $('#price_georgia_sale').val(data.price_georgia || 0);
        $('#price_usa_sale').val(data.price_usa || 0);
        $('#price_georgia_text').text(data.price_georgia || 0);
        $('#price_usa_text').text(data.price_usa || 0);

        if (typeof calculateSaleSummary === 'function') {
            calculateSaleSummary();
        }

        // size change event-ის დამუშავების შემდეგ გავაუქმოთ
        setTimeout(function() { isEditMode = false; }, 500);
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
        var isEditMode = false; // ← დაამატე

        $(document).on('change', '#product_id_sale', function() {
    const selected = $(this).find('option:selected');

    if (!isEditMode) {
        let priceGe = selected.data('price-ge') || 0;
        let priceUs = selected.data('price-us') || 0;
        $('#price_georgia_sale').val(priceGe);
        $('#price_georgia_text').text(priceGe);
        $('#price_usa_sale').val(priceUs);
        $('#price_usa_text').text(priceUs);
    }

    // სურათი — ყოველთვის განახლდება
    const imageUrl = selected.data('image');
    if (imageUrl) {
        $('#target_image').attr('src', imageUrl).show();
        $('#no_image_text').hide();
    } else {
        $('#target_image').hide();
        $('#no_image_text').show();
    }

    // ზომები — ყოველთვის განახლდება
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

            // disabled ველები FormData-ში არ ჩაერთვება —
            // გავხსნოთ დროებით, FormData ვაგროვოთ, შემდეგ დავხუროთ
            var $locked = form.find(':disabled');
            $locked.prop('disabled', false);
            var formData = new FormData(this);
            $locked.prop('disabled', true);

            $.ajax({
                url: url,
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    $('#modal-sale').modal('hide');
                    table.ajax.reload();
                    swal("წარმატება!", data.message, "success");
                },
                error: function(xhr) {
                    var msg = "მონაცემები ვერ შეინახა";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        try { msg = JSON.parse(xhr.responseText).message; } catch(e) {}
                    }
                    swal("შეცდომა", msg, "error");
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
        $('#btn-merge').show();
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
                table.ajax.url("{{ route('api.productsOut') }}").load();
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
// 🔍 Merge Search — customer-ის ყველა გასაერთიანებელი ორდერის ჩვენება
// =====================
$(document).on('click', '.merge-search-btn', function(e) {
    e.stopPropagation();
    var customerId = $(this).data('customer-id');

    // 1. DataTable-ს გავფილტვრავთ customer_id-ით (AJAX reload + custom param)
    table.ajax.url(
        "{{ route('api.productsOut') }}?merge_customer_id=" + customerId
    ).load(function() {
        // 2. reload-ის შემდეგ — ყველა checkbox მოვნიშნოთ
        setTimeout(function() {
            $('.row-check').prop('checked', true);
            toggleMergeBtn();
        }, 100);
    });
});

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
    var children  = (window._childrenStore || {})[parentId] || [];
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
// შვილ ორდერებს სტატუსის ხელით შეცვლა არ შეიძლება

        // payment + prices გაერთიანება
        var paymentPrices = '<span style="color:' + child.payment_color + '; font-weight:bold;">' + child.payment + '</span>'
            + '<hr style="margin:4px 0;">'
            + '<small><b>GE:</b> ' + child.price_georgia + ' ₾';
        if (isAdmin) paymentPrices += ' &nbsp; <b>US:</b> ' + child.price_usa + ' $';
        paymentPrices += '</small>';

        // customer + contact გაერთიანება
        var customerInfo = '<strong>' + child.customer_name + '</strong>'
            + '<hr style="margin:3px 0;">'
            + '<small>'
            + '<i class="fa fa-map-marker"></i> ' + child.customer_city + ', ' + child.customer_address + '<br>'
            + '<i class="fa fa-phone"></i> ' + child.customer_tel
            + (child.customer_alt ? ' / ' + child.customer_alt : '')
            + '</small>';

        // actions
        var deleteBtn2 = child.status_id == 4
            ? '<span class="btn btn-danger btn-xs disabled" style="opacity:0.4;"><i class="fa fa-trash"></i></span> '
            : '<a onclick="deleteData(' + child.id + ')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a> ';

        var actions = isAdmin
            ? '<a onclick="editForm(' + child.id + ')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i></a> ' +
              deleteBtn2 +
              '<a onclick="showStatusLog(' + child.id + ')" class="btn btn-warning btn-xs"><i class="fa fa-history"></i></a>'
            : '';

        var crossRefHtml = '';
        if (child.cross_ref && child.cross_ref.length > 0) {
            crossRefHtml = '<div style="margin-top:2px;"><small style="color:#31708f; font-style:italic;">' + child.cross_ref + '</small></div>';
        }

        var row = '<tr class="child-row-' + parentId + '" style="background:#fffde7;">' +
            '<td style="padding-left:20px;"><i class="fa fa-level-up fa-rotate-90" style="color:#aaa;"></i>' +
                '<div style="font-weight:600; color:#333; font-size:12px; margin-top:3px;">' + (child.order_number || ('#' + child.id)) + '</div>' +
                crossRefHtml +
            '</td>' +
            '<td>' + child.created_at + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + img + '</td>' +
            '<td><div>' + child.product_name + '</div>' +
                '<small class="text-muted">' + child.product_code + '</small>' +
                (child.product_size ? ' <span class="label label-info">' + child.product_size + '</span>' : '') +
            '</td>' +
            '<td>' + customerInfo + '</td>' +
            '<td>' + paymentPrices + '</td>' +
            '<td style="display:none;"></td>' + // cross_ref_html
            '<td style="display:none;"></td>' + // has_mergeable
            '<td style="display:none;"></td>' + // children_by_status
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
// $('#form-sale-content').on('submit', function() {
//     // დროებით ვააქტიურებთ დაბლოკილ ველებს გაგზავნისთვის
//     $(this).find(':disabled').prop('disabled', false);
// });

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
// ── SALE FORM: size change → stock info + FIFO ფასები ──
$(document).on('change', '#size_sale', function() {
    var prodId = $('#product_id_sale').val();
    var size   = $(this).val();

    if (!prodId || !size) {
        $('#sale_stock_info').hide();
        if (!isEditMode) {
            $('#price_georgia_sale').val(0);
            $('#price_georgia_text').text(0);
            $('#price_usa_sale').val(0);
            $('#price_usa_text').text(0);
        }
        return;
    }

    $.get("{{ route('warehouse.stockInfo') }}", 
        { product_id: prodId, size: size }, 
        function(data) {
            // FIFO ფასები მხოლოდ ახალი sale-ის დროს
            if (!isEditMode) {
                $.get("{{ url('api/fifo-prices') }}", 
                    { product_id: prodId, size: size },
                    function(fifo) {
                        $('#price_georgia_sale').val(fifo.price_georgia || 0);
                        $('#price_georgia_text').text(fifo.price_georgia || 0);
                        $('#price_usa_sale').val(fifo.cost_price || 0);
                        $('#price_usa_text').text(fifo.cost_price || 0);

                        if (typeof calculateSaleSummary === 'function') {
                            calculateSaleSummary();
                        }
                    }
                );
            } else {
                // edit-ის დროს display ველები ჩავავსოთ hidden-იდან
                $('#price_georgia_text').text($('#price_georgia_sale').val() || 0);
                $('#price_usa_text').text($('#price_usa_sale').val() || 0);
                if (typeof calculateSaleSummary === 'function') {
                    calculateSaleSummary();
                }
            }

            if (!data.found) {
                $('#sale_si_physical').text(0);
                $('#sale_si_incoming').text(0);
                $('#sale_si_reserved').text(0);
                $('#sale_si_available').text(0);
                $('#sale_si_badge').html('<span class="label label-danger">ნაშთი არ არის</span>');
                $('#sale_stock_info').show();
                return;
            }

            var avail = data.available;
            $('#sale_si_physical').text(data.physical_qty);
            $('#sale_si_incoming').text(data.incoming_qty);
            $('#sale_si_reserved').text(data.reserved_qty);
            $('#sale_si_available').text(avail);

            var color, badge;
            if (avail <= 0) {
                color = '#e74c3c';
                badge = '<span class="label label-danger">ნაშთი არ არის — მოლოდინში წავა</span>';
            } else if (avail <= 3) {
                color = '#f39c12';
                badge = '<span class="label label-warning">მცირე ნაშთი</span>';
            } else {
                color = '#00a65a';
                badge = '<span class="label label-success">ხელმისაწვდომია</span>';
            }
            $('#sale_si_available').css('color', color);
            $('#sale_si_badge').html(badge);
            $('#sale_stock_info').show();
        }
    );
});

// ── პროდუქტის შეცვლისას stock info დამალვა ──
$(document).on('change', '#product_id_sale', function() {
    $('#sale_stock_info').hide();
});

// ── modal დახურვისას stock info დამალვა ──
$('#modal-sale').on('hidden.bs.modal', function() {
    $('#sale_stock_info').hide();
});

// ════════════════════════════════════════════════════════════
// 🔄 CHANGE ORDER JS
// ════════════════════════════════════════════════════════════

window.openChangeModal = function(saleId) {
    $('#form-change')[0].reset();
    $('#change_original_sale_id').val(saleId);
    $('input[name="change_type"][value="return"]').prop('checked', true);
    $('#change-stock-info').hide();
    $('#change-price-diff-block').hide();
    $('#change-product-group').hide();
    $('#change_size').empty().append('<option value="">— ზომა —</option>');

    $.get("{{ url('productsOut') }}/" + saleId + "/edit", function(data) {
        $('#change-orig-id').text(data.id);
        $('#change-orig-product').text(data.current_product ? data.current_product.name : '');
        $('#change-orig-size').text(data.product_size || '');
        $('#change_product_id').val(data.product_id);
        $('#form-change').data('orig-price', parseFloat(data.price_georgia) || 0);
        $('#form-change').data('orig-product-id', data.product_id);
        $('#form-change').data('orig-size', data.product_size);

        var sizes = $('#change_product_id option[value="' + data.product_id + '"]').data('sizes') || '';
        // return არის default — ზომა ჩავავსოთ და ჩავკეტოთ
        var $sel = $('#change_size');
        $sel.empty().prop('disabled', true);
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $sel.val(data.product_size);
    });

    $('#modal-change').modal('show');
};

function populateChangeSizes(sizesRaw, selectedSize) {
    var $sel = $('#change_size');
    $sel.empty().append('<option value="">— ზომა —</option>');
    if (sizesRaw) {
        sizesRaw.toString().split(',').forEach(function(s) {
            s = s.trim();
            if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
        });
    }
    if (selectedSize) {
        $sel.val(selectedSize);
        loadChangeStockInfo();
    }
    $('#change-stock-info').hide();
}

function loadChangeStockInfo() {
    var changeType = $('input[name="change_type"]:checked').val();
    if (changeType === 'return') { $('#change-stock-info').hide(); return; }
    var prodId = $('#change_product_id').val();
    var size   = $('#change_size').val();
    if (!prodId || !size) { $('#change-stock-info').hide(); return; }

    $.get("{{ route('warehouse.stockInfo') }}", { product_id: prodId, size: size }, function(data) {
        if (!data.found) {
            $('#chg-si-physical, #chg-si-incoming, #chg-si-reserved, #chg-si-available').text(0);
            $('#chg-si-available').css('color', '#e74c3c');
            $('#chg-si-badge').html('<span class="label label-danger">ნაშთი არ არის — მოლოდინში წავა</span>');
        } else {
            var avail = data.available;
            $('#chg-si-physical').text(data.physical_qty);
            $('#chg-si-incoming').text(data.incoming_qty);
            $('#chg-si-reserved').text(data.reserved_qty);
            $('#chg-si-available').text(avail);
            var color = avail <= 0 ? '#e74c3c' : (avail <= 3 ? '#f39c12' : '#00a65a');
            var badge = avail <= 0
                ? '<span class="label label-danger">ნაშთი არ არის</span>'
                : (avail <= 3 ? '<span class="label label-warning">მცირე ნაშთი</span>'
                              : '<span class="label label-success">ხელმისაწვდომია</span>');
            $('#chg-si-available').css('color', color);
            $('#chg-si-badge').html(badge);
        }
        $('#change-stock-info').show();
    });
}

function updateChangePriceDiff() {
    var changeType = $('input[name="change_type"]:checked').val();
    if (changeType === 'return') { $('#change-price-diff-block').hide(); return; }
    var origPrice = parseFloat($('#form-change').data('orig-price') || 0);
    var newPrice  = parseFloat($('#change_product_id option:selected').data('price-ge') || 0);
    var diff = newPrice - origPrice;
    var diffEl = $('#change-price-diff');
    if (Math.abs(diff) < 0.01) {
        diffEl.text('სხვაობა არ არის').css('color', '#888');
    } else if (diff > 0) {
        diffEl.text('+' + diff.toFixed(2) + ' ₾ (კლიენტმა უნდა გადაიხადოს)').css('color', '#e74c3c');
    } else {
        diffEl.text(diff.toFixed(2) + ' ₾ (სასარგებლოდ)').css('color', '#27ae60');
    }
    $('#change-price-diff-block').show();
}

$(document).on('change', 'input[name="change_type"]', function() {
    var type = $(this).val();
    var origProductId = $('#form-change').data('orig-product-id');
    var origSize      = $('#form-change').data('orig-size');

    if (type === 'return') {
        // დაბრუნება — product + ზომა ავტომატური, ჩაკეტილი
        $('#change-product-group').hide();
        $('#change-price-diff-block').hide();
        $('#change-stock-info').hide();
        $('#change_product_id').val(origProductId);
        var sizes = $('#change_product_id option[value="' + origProductId + '"]').data('sizes') || '';
        var $sel = $('#change_size');
        $sel.empty();
        if (sizes) {
            sizes.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s) $sel.append('<option value="' + s + '">' + s + '</option>');
            });
        }
        $sel.val(origSize).prop('disabled', true); // ჩაკეტვა
    } else if (type === 'size') {
        // ზომის გაცვლა — product იგივე, ზომა სხვა (original გამოვრიცხოთ)
        $('#change-product-group').hide();
        $('#change_product_id').val(origProductId);
        var opt = $('#change_product_id option[value="' + origProductId + '"]');
        var $sel2 = $('#change_size');
        $sel2.empty().append('<option value="">— ზომა —</option>').prop('disabled', false);
        var sizes2 = opt.data('sizes') || '';
        if (sizes2) {
            sizes2.toString().split(',').forEach(function(s) {
                s = s.trim();
                if (s && s !== origSize) { // original ზომა გამოვრიცხოთ
                    $sel2.append('<option value="' + s + '">' + s + '</option>');
                }
            });
        }
        $('#change-stock-info').hide();
        updateChangePriceDiff();
    } else {
        // პროდუქტის გაცვლა
        $('#change-product-group').show();
        $('#change_product_id').val('');
        $('#change_size').empty().append('<option value="">— ზომა —</option>').prop('disabled', false);
        $('#change-stock-info').hide();
        updateChangePriceDiff();
    }
});

$(document).on('change', '#change_product_id', function() {
    var sizes = $(this).find('option:selected').data('sizes') || '';
    populateChangeSizes(sizes, null);
    $('#change-stock-info').hide();
    updateChangePriceDiff();
});

$(document).on('change', '#change_size', function() {
    loadChangeStockInfo();
    updateChangePriceDiff();
});

$('#form-change').on('submit', function(e) {
    e.preventDefault();
    var productId = $('#change_product_id').val();
    var size      = $('#change_size').val();
    if (!productId || !size) {
        swal('შეცდომა', 'პროდუქტი და ზომა სავალდებულოა', 'error');
        return;
    }
    // disabled ველები serialize-ში არ ჩაერთვება — დროებით გავხსნოთ
    var $disabledSize = $('#change_size').filter(':disabled');
    $disabledSize.prop('disabled', false);
    var formData = $(this).serialize();
    $disabledSize.prop('disabled', true);

    $.ajax({
        url:  "{{ url('productsOut/change') }}",
        type: 'POST',
        data: formData,
        success: function(res) {
            $('#modal-change').modal('hide');
            table.ajax.reload(null, false);
            swal({ title: '✅', text: res.message, type: 'success', timer: 2000 });
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა!';
            swal('შეცდომა', msg, 'error');
        }
    });
});

$('#modal-change').on('hidden.bs.modal', function() {
    $('#form-change')[0].reset();
    $('#change-price-diff-block').hide();
    $('#change-product-group').hide();
    $('#change-stock-info').hide();
    $('#change_size').prop('disabled', false);
});

    </script>
@endsection