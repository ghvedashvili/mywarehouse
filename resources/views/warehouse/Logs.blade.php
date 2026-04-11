@extends('layouts.master')

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
<style>
:root { --wh-dark:#222d32; --wh-border:#dee2e6; }
.wh-header { background:var(--wh-dark); color:#fff; padding:18px 25px 14px; border-radius:6px 6px 0 0; display:flex; align-items:center; justify-content:space-between; }
.wh-header h3 { margin:0; font-size:17px; font-weight:700; }
.wh-header .wh-subtitle { font-size:11px; color:#aaa; margin-top:2px; }
.wh-table thead th { background:#f4f4f4; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#555; border-bottom:2px solid var(--wh-border)!important; white-space:nowrap; }
.filter-bar { background:#fff; border:1px solid var(--wh-border); border-radius:6px; padding:14px 18px; margin:14px 0; }
</style>
@endsection

@section('content')
<div class="pb-3">
    <div class="wh-header">
        <div>
            <h3>📋 საწყობის ლოგი</h3>
            <div class="wh-subtitle">Warehouse Movement History</div>
        </div>
        <a href="{{ route('warehouse.index') }}" class="btn btn-default btn-sm fw-bold">
            <i class="fa fa-arrow-left"></i> საწყობი
        </a>
    </div>

    {{-- ფილტრები --}}
    <div class="filter-bar mt-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">პროდუქტი</label>
                <select id="filter-product" class="form-select form-select-sm select2-filter">
                    <option value="">— ყველა —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->product_code ? ' ('.$p->product_code.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:12px;">ოპერაცია</label>
                <select id="filter-action" class="form-select form-select-sm">
                    <option value="">— ყველა —</option>
                    <option value="purchase_in">📦 შემოსვლა</option>
                    <option value="purchase_rollback">↩ უკუქცევა (საწყობ→გზა)</option>
                    <option value="sale_out">🚚 გასვლა (გაყიდვა)</option>
                    <option value="defect">⚠️ წუნი</option>
                    <option value="lost">❌ დაკარგული</option>
                    <option value="adjustment">✏️ კორექცია (რაოდენობის შეცვლა)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:12px;">თარიღიდან</label>
                <input type="date" id="filter-date-from" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:12px;">თარიღამდე</label>
                <input type="date" id="filter-date-to" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                    <i class="fa fa-search"></i> ფილტრი
                </button>
                <button class="btn btn-default btn-sm" onclick="resetFilters()" title="გასუფთავება">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ჯამური სტატისტიკა --}}
    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="small-box bg-green mb-0">
                <div class="inner"><h3 id="stat-in">—</h3><p>📦 შემოსული</p></div>
                <div class="icon"><i class="fa fa-arrow-down"></i></div>
            </div>
        </div>
        <div class="col-3">
            <div class="small-box bg-blue mb-0">
                <div class="inner"><h3 id="stat-out">—</h3><p>🚚 გასული</p></div>
                <div class="icon"><i class="fa fa-arrow-up"></i></div>
            </div>
        </div>
        <div class="col-3">
            <div class="small-box bg-yellow mb-0">
                <div class="inner"><h3 id="stat-defect">—</h3><p>⚠️ წუნი</p></div>
                <div class="icon"><i class="fa fa-warning"></i></div>
            </div>
        </div>
        <div class="col-3">
            <div class="small-box bg-red mb-0">
                <div class="inner"><h3 id="stat-lost">—</h3><p>❌ დაკარგული</p></div>
                <div class="icon"><i class="fa fa-times"></i></div>
            </div>
        </div>
    </div>

    <table id="logs-table" class="table wh-table table-hover table-bordered">
        <thead>
            <tr>
                <th>თარიღი</th>
                <th>პროდუქტი</th>
                <th>ზომა</th>
                <th>ოპერაცია</th>
                <th>ცვლილება</th>
                <th>ნაშთი (მდე → შემდ.)</th>
                <th>შენიშვნა</th>
                <th>მომხ.</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
<script>
$(function() {

    $('.select2-filter').select2({ width: '100%', placeholder: '— ყველა —' });

    var logsTable = $('#logs-table').DataTable({
        processing: true, serverSide: true,
        order: [[0, 'desc']],
        pageLength: 25,
        ajax: {
            url: "{{ route('warehouse.apiLogs') }}",
            data: function(d) {
                d.product_id = $('#filter-product').val();
                d.action     = $('#filter-action').val();
                d.date_from  = $('#filter-date-from').val();
                d.date_to    = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'created_at',   width: '130px' },
            { data: 'product_name' },
            { data: 'product_size', defaultContent: '—', width: '60px' },
            { data: 'action_badge', orderable: false },
            { data: 'qty_badge',    orderable: false },
            { data: 'qty_badge',    orderable: false,
              render: function(data, type, row) {
                  return '<span class="text-muted" style="font-size:12px;">'
                       + row.qty_before + ' → ' + row.qty_after + '</span>';
              }
            },
            { data: 'note',         orderable: false, defaultContent: '—',
              render: function(v) {
                  if (!v) return '—';
                  return v.length > 40 ? '<span title="' + v + '">' + v.substring(0,40) + '…</span>' : v;
              }
            },
            { data: 'user_name',    orderable: false, width: '90px' },
        ],
        drawCallback: function() {
            // სტატისტიკა — მიმდინარე გვერდის მონაცემებიდან
            var d = this.api().rows().data();
            var ins = 0, out = 0, def = 0, lost = 0;
            d.each(function(r) {
                var ch = parseInt(r.qty_change) || 0;
                if (r.action === 'purchase_in')       ins  += ch;
                if (r.action === 'sale_out')           out  += Math.abs(ch);
                if (r.action === 'defect')             def  += Math.abs(ch);
                if (r.action === 'lost')               lost += Math.abs(ch);
            });
            $('#stat-in').text(ins);
            $('#stat-out').text(out);
            $('#stat-defect').text(def);
            $('#stat-lost').text(lost);
        }
    });

    window.applyFilters = function() { logsTable.ajax.reload(); };

    window.resetFilters = function() {
        $('#filter-product').val(null).trigger('change');
        $('#filter-action').val('');
        $('#filter-date-from, #filter-date-to').val('');
        logsTable.ajax.reload();
    };

    // Enter key — ფილტრი
    $(document).on('keypress', '#filter-date-from, #filter-date-to', function(e) {
        if (e.which === 13) applyFilters();
    });
});
</script>
@endsection