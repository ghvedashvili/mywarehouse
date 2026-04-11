@extends('layouts.master')

@section('top')
<style>
:root { --wh-green:#00a65a; --wh-orange:#f39c12; --wh-red:#dd4b39; --wh-blue:#357ca5; --wh-dark:#222d32; --wh-border:#dee2e6; }
.wh-header { background:var(--wh-dark); color:#fff; padding:18px 25px 14px; border-radius:6px 6px 0 0; display:flex; align-items:center; justify-content:space-between; }
.wh-header h3 { margin:0; font-size:17px; font-weight:700; }
.wh-header .wh-subtitle { font-size:11px; color:#aaa; margin-top:2px; }
.stat-cards { display:flex; gap:12px; margin-bottom:20px; }
.stat-card { flex:1; background:#fff; border:1px solid var(--wh-border); border-radius:8px; padding:16px 20px; border-left:4px solid var(--wh-green); box-shadow:0 1px 4px rgba(0,0,0,0.06); }
.stat-card.orange { border-left-color:var(--wh-orange); }
.stat-card.blue   { border-left-color:var(--wh-blue); }
.stat-card.red    { border-left-color:var(--wh-red); }
.stat-card .val { font-size:26px; font-weight:800; color:var(--wh-dark); line-height:1; }
.stat-card .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.6px; margin-top:4px; }
.wh-table thead th { background:#f4f4f4; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#555; border-bottom:2px solid var(--wh-border)!important; white-space:nowrap; }
.qty-badge { display:inline-block; min-width:32px; text-align:center; font-weight:700; padding:2px 8px; border-radius:4px; font-size:13px; }
.qty-physical  { background:#dff0d8; color:#3c763d; }
.qty-incoming  { background:#d9edf7; color:#31708f; }
.qty-reserved  { background:#fcf8e3; color:#8a6d3b; }
.qty-available { background:#222d32; color:#fff; }
.qty-zero      { background:#f2dede; color:#a94442; }
</style>
@endsection

@section('content')
<div class="pb-3">
    <div class="wh-header">
        <div>
            <h3>🏭 საწყობი — ნაშთი</h3>
            <div class="wh-subtitle">Warehouse Stock Management</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm fw-bold" onclick="openWriteOffModal()">
                <i class="fa fa-minus-circle"></i> ჩამოწერა / წუნი
            </button>
            <a href="{{ route('warehouse.logs') }}" class="btn btn-default btn-sm fw-bold">
                <i class="fa fa-history"></i> ყველა ლოგი
            </a>
            <a href="{{ url('purchases') }}" class="btn btn-info btn-sm fw-bold">
                <i class="fa fa-cart-shopping"></i> შესყიდვების ორდერები
            </a>
        </div>
    </div>

    <div class="stat-cards mt-3">
        <div class="stat-card"><div class="val" id="stat-physical">—</div><div class="lbl">📦 ფიზიკური ნაშთი</div></div>
        <div class="stat-card orange"><div class="val" id="stat-incoming">—</div><div class="lbl">🚚 გზაში</div></div>
        <div class="stat-card blue"><div class="val" id="stat-reserved">—</div><div class="lbl">🔒 დაჯავშნული</div></div>
        <div class="stat-card red"><div class="val" id="stat-low">—</div><div class="lbl">⚠️ მცირე ნაშთი</div></div>
    </div>

    <table id="stock-table" class="table wh-table table-hover table-bordered">
        <thead>
            <tr>
                <th>პროდუქტი</th><th>კოდი</th><th>ზომა</th>
                <th>📦 ფიზიკური</th><th>🚚 გზაში</th><th>🔒 დაჯავშნ.</th>
                <th>⚠️ წუნი</th><th>✅ ხელმისაწვდომი</th><th>🧮 FIFO თვითღ.</th><th>სტატუსი</th><th></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
{{-- Write-Off Modal --}}
<div class="modal fade" id="modal-writeoff" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius:8px;">
            <div class="modal-header" style="background:#e67e22; color:#fff; border-radius:8px 8px 0 0;">
                <h5 class="modal-title fw-bold">📉 ჩამოწერა / წუნი</h5>
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
                        <div class="fw-bold" style="font-size:22px; color:#c0392b;" id="wo-defect-now">0</div>
                        <div class="text-muted" style="font-size:11px;">⚠️ წუნი</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold" style="font-size:22px; color:#555;" id="wo-physical">0</div>
                        <div class="text-muted" style="font-size:11px;">📦 ფიზ. ჯამი</div>
                    </div>
                </div>

                {{-- ტიპი --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px; text-transform:uppercase;">ოპერაცია</label>
                    <div class="d-flex gap-2">
                        <button type="button" id="wo-type-writeoff" class="btn btn-danger w-50 wo-type-btn active-type"
                                onclick="selectWoType('writeoff')">
                            ❌ ჩამოწერა
                        </button>
                        <button type="button" id="wo-type-defect" class="btn btn-outline-warning w-50 wo-type-btn"
                                onclick="selectWoType('defect')">
                            ⚠️ წუნი
                        </button>
                    </div>
                    <small class="text-muted mt-1 d-block" id="wo-type-hint">
                        ჩამოწერა: physical_qty-დან სრულად გამოვა
                    </small>
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
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-log" style="width:680px;" data-bs-backdrop="false">
    <div class="offcanvas-header" style="background:#222d32; color:#fff;">
        <h5 class="offcanvas-title fw-bold" id="offcanvas-log-title">📋 საწყობის ისტორია</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <table id="log-table" class="table table-sm table-hover table-bordered mb-0" style="font-size:13px;">
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

@endsection

@section('bot')
<script>
$(function() {
    var logTable = null;

    var stockTable = $('#stock-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('warehouse.apiStock') }}",
        columns: [
            {data:'product_name'},{data:'product_code'},{data:'size'},
            {data:'physical_qty', render: v => `<span class="qty-badge ${v>0?'qty-physical':'qty-zero'}">${v}</span>`},
            {data:'incoming_qty', render: v => `<span class="qty-badge ${v>0?'qty-incoming':'qty-zero'}">${v}</span>`},
            {data:'reserved_qty', render: v => `<span class="qty-badge ${v>0?'qty-reserved':'qty-zero'}">${v}</span>`},
            {data:'defect_qty',   render: v => `<span class="qty-badge" style="min-width:32px;text-align:center;font-weight:700;padding:2px 8px;border-radius:4px;font-size:13px;${v>0?'background:#fdecea;color:#c0392b;':'background:#f4f4f4;color:#aaa;'}">${v}</span>`},
            {data:'available',    render: v => `<span class="qty-badge ${v>0?'qty-available':'qty-zero'}">${v}</span>`},
            {data:'fifo_cost',    render: v => `<span style="color:#8e44ad;font-weight:700;">$${v}</span>`},
            {data:'status_badge', orderable:false},
            {data:'action',       orderable:false},
        ],
        drawCallback: function() {
            var d=this.api().rows().data(), ph=0,inc=0,res=0,low=0;
            d.each(function(r){ ph+=parseInt(r.physical_qty)||0; inc+=parseInt(r.incoming_qty)||0; res+=parseInt(r.reserved_qty)||0; if(parseInt(r.available)<=3)low++; });
            $('#stat-physical').text(ph); $('#stat-incoming').text(inc); $('#stat-reserved').text(res); $('#stat-low').text(low);
        }
    });

    // ══ WRITE-OFF MODAL ══
    var woStockData   = [];   // ყველა available stock
    var woCurrentType = 'writeoff';

    window.openWriteOffModal = function() {
        // reset
        $('#wo-product').html('<option value="">— აირჩიე —</option>');
        $('#wo-size-wrap, #wo-stock-info, #wo-qty-wrap, #wo-note-wrap').hide();
        $('#btn-writeoff-save').hide();
        $('#wo-qty').val(1);
        $('#wo-note').val('');
        selectWoType('writeoff');

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
        $('#wo-defect-now').text(row.defect);
        $('#wo-physical').text(row.physical);
        $('#wo-qty').val(1).attr('max', row.available);
        $('#wo-qty-hint').text('მაქს. ხელმისაწვდომი: ' + row.available);
        $('#wo-stock-info, #wo-qty-wrap, #wo-note-wrap').show();
        $('#btn-writeoff-save').show();
    });

    window.selectWoType = function(type) {
        woCurrentType = type;
        if (type === 'writeoff') {
            $('#wo-type-writeoff').removeClass('btn-outline-danger').addClass('btn-danger');
            $('#wo-type-defect').removeClass('btn-warning').addClass('btn-outline-warning');
            $('#wo-type-hint').text('ჩამოწერა: physical_qty-დან სრულად გამოვა (დაიკარგა)');
        } else {
            $('#wo-type-defect').removeClass('btn-outline-warning').addClass('btn-warning');
            $('#wo-type-writeoff').removeClass('btn-danger').addClass('btn-outline-danger');
            $('#wo-type-hint').text('წუნი: physical_qty-ში რჩება, მაგრამ ხელმისაწვდომი აღარ არის');
        }
    };

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