@extends('layouts.master')
@section('page_title')<i class="fa fa-warehouse me-2" style="color:#8e44ad;"></i>საწყობი@endsection

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
:root { --wh-green:#00a65a; --wh-orange:#f39c12; --wh-red:#dd4b39; --wh-blue:#357ca5; --wh-purple:#8e44ad; --wh-dark:#222d32; --wh-border:#dee2e6; }
.stat-card { background:#fff; border:1px solid var(--wh-border); border-radius:8px; padding:14px 18px; border-left:4px solid var(--wh-green); box-shadow:0 1px 4px rgba(0,0,0,0.06); }
.stat-card.orange { border-left-color:var(--wh-orange); }
.stat-card.blue   { border-left-color:var(--wh-blue); }
.stat-card.purple { border-left-color:var(--wh-purple); }
.stat-card.red    { border-left-color:var(--wh-red); }
.stat-card .val { font-size:26px; font-weight:800; color:var(--wh-dark); line-height:1; }
.stat-card .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.6px; margin-top:4px; }
.wh-table thead th { background:#f4f4f4; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#555; border-bottom:2px solid var(--wh-border)!important; white-space:nowrap; }
.qty-badge { display:inline-block; min-width:32px; text-align:center; font-weight:700; padding:2px 8px; border-radius:4px; font-size:13px; }
.qty-physical         { background:#dff0d8; color:#3c763d; }
.qty-incoming         { background:#d9edf7; color:#31708f; }
.qty-return-incoming  { background:#f5e6ff; color:#8e44ad; }
.qty-reserved         { background:#fcf8e3; color:#8a6d3b; }
.qty-available        { background:#222d32; color:#fff; }
.qty-zero             { background:#f2dede; color:#a94442; }

/* Financial summary bar */
.fin-bar {
    display: flex; align-items: center; flex-wrap: wrap; gap: 0;
    background: #fff; border: 1px solid var(--wh-border); border-radius: 8px;
    padding: 10px 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.fin-item {
    display: flex; align-items: center; gap: 10px;
    flex: 1 1 0; min-width: 160px; padding: 4px 8px;
}
.fin-icon { font-size: 22px; line-height: 1; }
.fin-val  { font-size: 18px; font-weight: 800; color: var(--wh-dark); line-height: 1.1; }
.fin-lbl  { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.fin-sep  { width: 1px; height: 40px; background: var(--wh-border); margin: 0 4px; flex-shrink: 0; }

/* Responsive expand control dot */
table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
    background-color: var(--wh-green);
    border-radius: 50%;
}
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-warehouse me-2" style="color:#8e44ad;"></i>საწყობი</h2>
            <p class="mod-subtitle">ნაშთების მართვა და კონტროლი</p>
        </div>
        <div class="mod-actions">
            <button class="btn btn-warning btn-sm" onclick="openWriteOffModal()">
                <i class="fa fa-minus-circle me-1"></i><span class="d-none d-sm-inline"> ჩამოწერა</span>
            </button>
            <a href="{{ route('warehouse.logs') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-history me-1"></i><span class="d-none d-sm-inline"> ლოგი</span>
            </a>
            <a href="{{ url('purchases') }}" class="btn btn-info btn-sm">
                <i class="fa fa-cart-shopping me-1"></i><span class="d-none d-sm-inline"> შესყიდვები</span>
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-2 mb-2">
        <div class="col-6 col-md">
            <div class="stat-card"><div class="val" id="stat-physical">—</div><div class="lbl">📦 ფიზიკური ნაშთი</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card orange"><div class="val" id="stat-incoming">—</div><div class="lbl">🚚 გზაში</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card purple"><div class="val" id="stat-return-incoming">—</div><div class="lbl">↩ დაბრუნება გზაში</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card blue"><div class="val" id="stat-reserved">—</div><div class="lbl">🔒 დაჯავშნული</div></div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card red"><div class="val" id="stat-low">—</div><div class="lbl">⚠️ მცირე ნაშთი</div></div>
        </div>
    </div>

    {{-- Financial summary bar --}}
    <div id="fin-bar" class="fin-bar mb-3">
        <div class="fin-item">
            <span class="fin-icon">✅</span>
            <div>
                <div class="fin-val" id="fin-available">—</div>
                <div class="fin-lbl">ხელმისაწვდომი ნაშთი</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">💵</span>
            <div>
                <div class="fin-val" id="fin-cost">—</div>
                <div class="fin-lbl">ჯამური თვითღირებულება</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">📈</span>
            <div>
                <div class="fin-val" id="fin-revenue">—</div>
                <div class="fin-lbl">მოსალოდნელი შემოსავალი</div>
            </div>
        </div>
        <div class="fin-sep"></div>
        <div class="fin-item">
            <span class="fin-icon">💰</span>
            <div>
                <div class="fin-val" id="fin-profit">—</div>
                <div class="fin-lbl">მოსალოდნელი მოგება</div>
            </div>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-toolbar">
            <select id="dt-page-length" class="form-select form-select-sm" style="width:75px;">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="-1">ყველა</option>
            </select>
            <div class="mod-toolbar-search">
                <i class="fa fa-search search-icon"></i>
                <input id="dt-search" type="search" class="form-control form-control-sm" placeholder="ძებნა...">
            </div>
            <select id="filter-category" class="form-select form-select-sm" style="width:170px;">
                <option value="">ყველა კატეგორია</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="table-responsive">
            <table id="stock-table" class="table wh-table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th></th><th>პროდუქტი</th><th>კოდი</th><th>ზომა</th>
                        <th>📦 ფიზ.</th><th>🚚 გზაში</th><th>↩ დაბრ. გზაში</th><th>🔒 დაჯავშნ.</th>
                        <th>✅ ხელმისაწვდ.</th><th>🧮 FIFO</th><th>სტატუსი</th><th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap --}}
{{-- Image Zoom Modal --}}
<div class="modal fade" id="modal-img-zoom" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:90vw;width:auto;">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" style="z-index:10;"></button>
                <img id="zoom-img-src" src="" style="max-height:85vh;max-width:85vw;border-radius:8px;object-fit:contain;">
            </div>
        </div>
    </div>
</div>

{{-- Write-Off Modal --}}
<div class="modal fade" id="modal-writeoff" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#e67e22; color:#fff; border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📉 ჩამოწერა</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- პროდუქტი --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">პროდუქტი</label>
                    <select id="wo-product" class="form-select">
                        <option value="">— აირჩიე —</option>
                    </select>
                </div>

                {{-- ზომა --}}
                <div class="mb-3" id="wo-size-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">ზომა</label>
                    <select id="wo-size" class="form-select"></select>
                </div>

                {{-- ნაშთის ინფო --}}
                <div id="wo-stock-info" style="display:none;"
                     class="p-3 rounded mb-3 d-flex gap-4 align-items-center"
                     style="background:#f9f9f9; border:1px solid #ddd;">
                    <div class="text-center">
                        <div class="fw-bold text-success" style="font-size:22px;" id="wo-available">0</div>
                        <div class="text-muted" style="font-size:11px;">✅ ხელმისაწვდომი</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold" style="font-size:22px; color:#555;" id="wo-physical">0</div>
                        <div class="text-muted" style="font-size:11px;">📦 ფიზ. ჯამი</div>
                    </div>
                </div>

                {{-- რაოდენობა --}}
                <div class="mb-3" id="wo-qty-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">რაოდენობა</label>
                    <input type="number" id="wo-qty" class="form-control text-center fw-bold"
                           min="1" value="1" style="font-size:20px;">
                    <small class="text-muted" id="wo-qty-hint"></small>
                </div>

                {{-- შენიშვნა --}}
                <div class="mb-2" id="wo-note-wrap" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">შენიშვნა</label>
                    <input type="text" id="wo-note" class="form-control form-control-sm"
                           placeholder="სურვილისამებრ...">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-danger" id="btn-writeoff-save"
                        onclick="submitWriteOff()" style="display:none;">
                    <i class="fa fa-check"></i> დადასტურება
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Offcanvas: პროდუქტის ლოგი --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-log"
     style="width:min(680px, 100vw);" data-bs-backdrop="false">
    <div class="offcanvas-header" style="background:#222d32; color:#fff;">
        <h5 class="offcanvas-title fw-bold" id="offcanvas-log-title">📋 საწყობის ისტორია</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="table-responsive h-100">
        <table id="log-table" class="table table-sm table-hover table-bordered mb-0 w-100" style="font-size:13px;">
            <thead>
                <tr>
                    <th>თარიღი</th>
                    <th>ოპერაცია</th>
                    <th>ცვლილება</th>
                    <th>შენიშვნა</th>
                    <th>მომხ.</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
window.whZoom = function(url) {
    document.getElementById('zoom-img-src').src = url;
    new bootstrap.Modal(document.getElementById('modal-img-zoom')).show();
};

$(function() {
    var logTable = null;

    // ─── Financial summary bar ────────────────────────────────────────
    $.getJSON("{{ route('warehouse.financials') }}", function(d) {
        $('#fin-available').text(d.available + ' ც');
        $('#fin-cost').text('$' + parseFloat(d.cost).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}));
        $('#fin-revenue').text(parseFloat(d.revenue).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₾');
        var profit = parseFloat(d.profit);
        $('#fin-profit').text(profit.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₾')
                        .css('color', profit >= 0 ? 'var(--wh-green)' : 'var(--wh-red)');
    });
    // ─────────────────────────────────────────────────────────────────

    var stockTable = $('#stock-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
        pageLength: 25,
        dom: 't<"d-flex justify-content-between align-items-center mt-2 px-2"ip>',
        ajax: {
            url: "{{ route('warehouse.apiStock') }}",
            data: function(d) { d.category_id = $('#filter-category').val(); }
        },
        columns: [
            // priority: დაბალი რიცხვი = პირველი რჩება ეკრანზე, მაღალი = პირველი იმალება
            {data:'product_image', orderable:false, searchable:false, responsivePriority: 3, width:'46px'},
            {data:'product_name', responsivePriority: 1},
            {data:'product_code', responsivePriority: 9},
            {data:'size',         responsivePriority: 2},
            {data:'physical_qty', responsivePriority: 5,
             render: v => `<span class="qty-badge ${v>0?'qty-physical':'qty-zero'}">${v}</span>`},
            {data:'incoming_qty', responsivePriority: 8,
             render: v => `<span class="qty-badge ${v>0?'qty-incoming':'qty-zero'}">${v}</span>`},
            {data:'return_incoming_qty', responsivePriority: 9,
             render: v => `<span class="qty-badge ${v>0?'qty-return-incoming':'qty-zero'}">${v}</span>`},
            {data:'reserved_qty', responsivePriority: 10,
             render: v => `<span class="qty-badge ${v>0?'qty-reserved':'qty-zero'}">${v}</span>`},
            {data:'available',    responsivePriority: 3,
             render: v => `<span class="qty-badge ${v>0?'qty-available':'qty-zero'}">${v}</span>`},
            {data:'fifo_cost', orderable:false, responsivePriority: 10},
            {data:'status_badge', orderable:false, responsivePriority: 6},
            {data:'action',       orderable:false, responsivePriority: 4},
        ],
        drawCallback: function() {
            var d=this.api().rows().data(), ph=0,inc=0,ret=0,res=0,low=0;
            d.each(function(r){ ph+=parseInt(r.physical_qty)||0; inc+=parseInt(r.incoming_qty)||0; ret+=parseInt(r.return_incoming_qty)||0; res+=parseInt(r.reserved_qty)||0; if(parseInt(r.available)<=3)low++; });
            $('#stat-physical').text(ph); $('#stat-incoming').text(inc); $('#stat-return-incoming').text(ret); $('#stat-reserved').text(res); $('#stat-low').text(low);
        }
    });

    $('#dt-search').on('keyup', function() { stockTable.search(this.value).draw(); });
    $('#dt-page-length').on('change', function() { stockTable.page.len(this.value).draw(); });
    $('#filter-category').select2({ placeholder: 'ყველა კატეგორია', allowClear: true, width: '170px' });
    $('#filter-category').on('change', function() { stockTable.ajax.reload(); });

    // ══ WRITE-OFF MODAL ══
    var woStockData   = [];
    var woCurrentType = 'writeoff';

    window.openWriteOffModal = function() {
        $('#wo-product').html('<option value="">— აირჩიე —</option>');
        $('#wo-size-wrap, #wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
        $('#btn-writeoff-save').hide();
        $('#wo-qty').val(1);
        $('#wo-note').val('');

        // load available stock
        $.get("{{ route('warehouse.availableStock') }}", function(data) {
            woStockData = data;

            // unique products
            var seen = {};
            data.forEach(function(r) {
                if (!seen[r.product_id]) {
                    seen[r.product_id] = true;
                    $('#wo-product').append(
                        '<option value="' + r.product_id + '">' + r.product_name + '</option>'
                    );
                }
            });
        });

        new bootstrap.Modal(document.getElementById('modal-writeoff')).show();
    };

    $('#wo-product').on('change', function() {
        var pid = $(this).val();
        $('#wo-size').empty();
        $('#wo-size-wrap, #wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
        $('#btn-writeoff-save').hide();

        if (!pid) return;

        var sizes = woStockData.filter(r => r.product_id == pid);
        if (sizes.length === 1) {
            // ერთი ზომა — პირდაპირ გადავდგათ
            $('#wo-size').append('<option value="' + sizes[0].size + '">' + sizes[0].size + '</option>');
            $('#wo-size-wrap').show();
            $('#wo-size').trigger('change');
        } else {
            $('#wo-size').append('<option value="">— ზომა —</option>');
            sizes.forEach(function(r) {
                $('#wo-size').append('<option value="' + r.size + '">' + r.size + '</option>');
            });
            $('#wo-size-wrap').show();
        }
    });

    $('#wo-size').on('change', function() {
        var pid  = $('#wo-product').val();
        var size = $(this).val();
        if (!pid || !size) {
            $('#wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
            $('#btn-writeoff-save').hide();
            return;
        }

        var row = woStockData.find(r => r.product_id == pid && r.size === size);
        if (!row) return;

        $('#wo-available').text(row.available);
        $('#wo-physical').text(row.physical);
        $('#wo-qty').val(1).attr('max', row.available);
        $('#wo-qty-hint').text('მაქს. ხელმისაწვდომი: ' + row.available);
        $('#wo-stock-info, #wo-qty-wrap, #wo-note-wrap').show();
        $('#btn-writeoff-save').show();
    });

    window.submitWriteOff = function() {
        var pid  = $('#wo-product').val();
        var size = $('#wo-size').val();
        var qty  = parseInt($('#wo-qty').val()) || 0;
        var max  = parseInt($('#wo-qty').attr('max')) || 0;

        if (!pid || !size)  { swal('შეცდომა', 'აირჩიეთ პროდუქტი და ზომა', 'error'); return; }
        if (qty < 1)        { swal('შეცდომა', 'რაოდენობა უნდა იყოს მინიმუმ 1', 'error'); return; }
        if (qty > max)      { swal('შეცდომა', 'მაქსიმუმ ' + max + ' ერთ.', 'error'); return; }

        $('#btn-writeoff-save').prop('disabled', true).text('...');

        $.ajax({
            url: "{{ route('warehouse.writeOff') }}",
            type: 'POST',
            data: {
                product_id: pid,
                size:       size,
                qty:        qty,
                type:       woCurrentType,
                note:       $('#wo-note').val(),
                _token:     "{{ csrf_token() }}"
            },
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-writeoff')).hide();
                stockTable.ajax.reload();
                swal({ title: '✅', text: res.message, type: 'success', timer: 2000 });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'შეცდომა!';
                swal({ title: 'შეცდომა', text: msg, type: 'error' });
            },
            complete: function() {
                $('#btn-writeoff-save').prop('disabled', false).html('<i class="fa fa-check"></i> დადასტურება');
            }
        });
    };

    // ══ OFFCANVAS LOG ══
    window.openStockLog = function(productId, productName, size) {
        $('#offcanvas-log-title').text('📋 ' + productName + (size ? ' / ' + size : ''));

        // DataTable init ან reload
        if (logTable) {
            logTable.ajax.url(buildLogUrl(productId, size)).load();
        } else {
            logTable = $('#log-table').DataTable({
                processing: true, serverSide: true,
                ajax: buildLogUrl(productId, size),
                order: [[0, 'desc']],
                pageLength: 20,
                columns: [
                    {data:'created_at',   width:'120px'},
                    {data:'action_badge', orderable:false},
                    {data:'qty_badge',    orderable:false},
                    {data:'note',         orderable:false, defaultContent:'—',
                     render: v => v ? `<span title="${v}">${v.length>30?v.substring(0,30)+'…':v}</span>` : '—'},
                    {data:'user_name',    orderable:false, width:'80px'},
                ],
            });
        }

        new bootstrap.Offcanvas(document.getElementById('offcanvas-log')).show();
    };

    function buildLogUrl(productId, size) {
        return "{{ route('warehouse.apiLogs') }}?product_id=" + productId + "&size=" + encodeURIComponent(size);
    }
});
</script>
@endsection