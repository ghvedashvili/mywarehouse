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
        <a href="{{ url('purchases') }}" class="btn btn-info btn-sm fw-bold">
            <i class="fa fa-cart-shopping"></i> შესყიდვების ორდერები
        </a>
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
                <th>✅ ხელმისაწვდომი</th><th>🧮 FIFO თვითღ.</th><th>სტატუსი</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection

@section('bot')
<script>
$(function() {
    var stockTable = $('#stock-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('warehouse.apiStock') }}",
        columns: [
            {data:'product_name'},{data:'product_code'},{data:'size'},
            {data:'physical_qty', render: v => `<span class="qty-badge ${v>0?'qty-physical':'qty-zero'}">${v}</span>`},
            {data:'incoming_qty', render: v => `<span class="qty-badge ${v>0?'qty-incoming':'qty-zero'}">${v}</span>`},
            {data:'reserved_qty', render: v => `<span class="qty-badge ${v>0?'qty-reserved':'qty-zero'}">${v}</span>`},
            {data:'available',    render: v => `<span class="qty-badge ${v>0?'qty-available':'qty-zero'}">${v}</span>`},
            {data:'fifo_cost',    render: v => `<span style="color:#8e44ad;font-weight:700;">$${v}</span>`},
            {data:'status_badge', orderable:false},
        ],
        drawCallback: function() {
            var d=this.api().rows().data(), ph=0,inc=0,res=0,low=0;
            d.each(function(r){ ph+=parseInt(r.physical_qty)||0; inc+=parseInt(r.incoming_qty)||0; res+=parseInt(r.reserved_qty)||0; if(parseInt(r.available)<=3)low++; });
            $('#stat-physical').text(ph); $('#stat-incoming').text(inc); $('#stat-reserved').text(res); $('#stat-low').text(low);
        }
    });
});
</script>
@endsection