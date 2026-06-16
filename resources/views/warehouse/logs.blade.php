@extends('layouts.master')

@section('top')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
.log-stat {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 14px 10px; border-right: 1px solid #f1f5f9; text-align: center; flex: 1;
}
.log-stat:last-child { border-right: none; }
.log-stat-icon {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; margin-bottom: 7px;
}
.log-stat-val { font-size: 20px; font-weight: 800; line-height: 1; color: #1e293b; }
.log-stat-lbl { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-top: 4px; }
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-clipboard-list me-2" style="color:#0ea5e9;"></i>საწყობის ლოგი</h2>
            <p class="mod-subtitle">Warehouse Movement History</p>
        </div>
        <div class="mod-actions">
            <a href="{{ route('warehouse.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> საწყობი
            </a>
        </div>
    </div>

    <div class="mod-card">

        {{-- Stats row --}}
        <div class="d-flex border-bottom">
            <div class="log-stat">
                <div class="log-stat-icon" style="background:#f0fdf4; color:#16a34a;"><i class="fa fa-arrow-down"></i></div>
                <div class="log-stat-val text-success" id="stat-in">—</div>
                <div class="log-stat-lbl">შემოსული</div>
            </div>
            <div class="log-stat">
                <div class="log-stat-icon" style="background:#eff6ff; color:#2563eb;"><i class="fa fa-arrow-up"></i></div>
                <div class="log-stat-val text-primary" id="stat-out">—</div>
                <div class="log-stat-lbl">გასული</div>
            </div>
            <div class="log-stat">
                <div class="log-stat-icon" style="background:#fef2f2; color:#dc2626;"><i class="fa fa-xmark"></i></div>
                <div class="log-stat-val text-danger" id="stat-lost">—</div>
                <div class="log-stat-lbl">დაკარგული</div>
            </div>
        </div>

        {{-- Filter toolbar --}}
        <div class="mod-toolbar flex-wrap">
            <div style="min-width:200px; flex:2;">
                <select id="filter-product" class="form-select form-select-sm select2-filter">
                    <option value="">— პროდუქტი: ყველა —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->product_code ? ' ('.$p->product_code.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:160px; flex:1;">
                <select id="filter-action" class="form-select form-select-sm">
                    <option value="">— ოპერაცია: ყველა —</option>
                    <option value="purchase_in">📦 შემოსვლა</option>
                    <option value="purchase_rollback">↩ უკუქცევა</option>
                    <option value="sale_out">🚚 გასვლა (გაყიდვა)</option>
                    <option value="lost">❌ დაკარგული</option>
                    <option value="adjustment">✏️ კორექცია</option>
                </select>
            </div>
            <input type="date" id="filter-date-from" class="form-control form-control-sm" style="width:140px;">
            <input type="date" id="filter-date-to"   class="form-control form-control-sm" style="width:140px;">
            <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                <i class="fa fa-search me-1"></i> ფილტრი
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()" title="გასუფთავება">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table id="logs-table" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-dark">
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

    </div>
</div>
@endsection

@section('bot')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function() {

    $('.select2-filter').select2({ width: '100%', placeholder: '— ყველა —' });

    var logsTable = $('#logs-table').DataTable({
        processing: true, serverSide: true,
        responsive: true,
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
            { data: 'created_at',   width: '130px', responsivePriority: 2 },
            { data: 'product_name',                 responsivePriority: 1 },
            { data: 'product_size', defaultContent: '—', width: '60px', responsivePriority: 3 },
            { data: 'action_badge', orderable: false, responsivePriority: 4 },
            { data: 'qty_badge',    orderable: false, responsivePriority: 5 },
            { data: 'qty_badge',    orderable: false, responsivePriority: 8,
              render: function(data, type, row) {
                  return '<span class="text-muted" style="font-size:12px;">'
                       + row.qty_before + ' → ' + row.qty_after + '</span>';
              }
            },
            { data: 'note', orderable: false, defaultContent: '—', responsivePriority: 7,
              render: function(v) {
                  if (!v) return '—';
                  return v.length > 40 ? '<span title="' + v + '">' + v.substring(0,40) + '…</span>' : v;
              }
            },
            { data: 'user_name', orderable: false, width: '90px', responsivePriority: 6 },
        ],
        drawCallback: function() {
            var d = this.api().rows().data();
            var ins = 0, out = 0, lost = 0;
            d.each(function(r) {
                var ch = parseInt(r.qty_change) || 0;
                if (r.action === 'purchase_in') ins  += ch;
                if (r.action === 'sale_out')    out  += Math.abs(ch);
                if (r.action === 'lost')        lost += Math.abs(ch);
            });
            $('#stat-in').text(ins);
            $('#stat-out').text(out);
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

    $(document).on('keypress', '#filter-date-from, #filter-date-to', function(e) {
        if (e.which === 13) applyFilters();
    });
});
</script>
@endsection
