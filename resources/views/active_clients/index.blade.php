@extends('layouts.master')
@section('page_title')<i class="fa fa-address-book me-2" style="color:#16a34a;"></i>აქტიური კლიენტები@endsection

@section('content')
<div style="padding:20px; font-family:'Segoe UI',sans-serif;">

    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <p style="margin:0 0 16px;color:#636e72;font-size:13.5px;">
            კლიენტები, რომლებსაც აქვთ არჩეულ ფილტრებთან შესაბამისი ორდერი (სტატუსი + გადახდის მდგომარეობა ერთდროულად).
        </p>

        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
            <select id="filter-status" class="form-select form-select-sm" style="width:auto;min-width:170px;">
                <option value="">— ყველა სტატუსი —</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>

            <select id="filter-payment" class="form-select form-select-sm" style="width:auto;min-width:190px;">
                <option value="">— გადახდის ყველა მდგომარეობა —</option>
                <option value="full">✅ სრულად გადახდილი</option>
                <option value="partial">🟡 ნაწილობრივ გადახდილი</option>
                <option value="unpaid">🔴 გადაუხდელი</option>
            </select>

            <div class="input-group input-group-sm" style="width:auto;">
                <input type="text" id="dt-search" class="form-control" placeholder="ძებნა..." style="min-width:160px;">
                <button type="button" id="btn-search" class="btn btn-outline-secondary">
                    <i class="fa fa-magnifying-glass"></i>
                </button>
            </div>

            <select id="dt-page-length" class="form-select form-select-sm" style="width:auto;">
                <option value="25">25 ხაზი</option>
                <option value="50">50 ხაზი</option>
                <option value="100">100 ხაზი</option>
            </select>

            <a id="btn-export" href="{{ route('active-clients.exportExcel') }}" target="_blank"
               class="btn btn-success btn-sm ms-auto">
                <i class="fa fa-file-excel me-1"></i> ექსელში ექსპორტი
            </a>
        </div>

        <div class="table-responsive">
            <table id="active-clients-table" class="table table-sm table-bordered align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>სახელი</th>
                        <th>ტელეფონი</th>
                        <th>დამატ. ტელ.</th>
                        <th>ქალაქი</th>
                        <th>მისამართი</th>
                        <th>ემაილი</th>
                        <th>კომენტარი</th>
                        <th class="text-center">ორდ. რაოდ.</th>
                        <th>ორდერების ნომრები</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('bot')
<script>
function qsParams() {
    return {
        status_id:      $('#filter-status').val(),
        payment_status: $('#filter-payment').val(),
    };
}

var table = $('#active-clients-table').DataTable({
    processing: true,
    serverSide: true,
    paging: true,
    pageLength: 25,
    order: [],
    ajax: {
        url: "{{ route('active-clients.api') }}",
        data: function(d) { Object.assign(d, qsParams()); }
    },
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'tel' },
        { data: 'alternative_tel' },
        { data: 'city_name' },
        { data: 'address' },
        { data: 'email' },
        { data: 'comment' },
        { data: 'order_count', className: 'text-center', orderable: false },
        { data: 'order_numbers', orderable: false },
    ],
    language: {
        processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
        info: '_START_–_END_ / _TOTAL_',
        paginate: { previous: '‹', next: '›' }
    },
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
});

function reloadTable() { table.ajax.reload(); }

function doSearch() { table.search($('#dt-search').val()).draw(); }
$('#btn-search').on('click', doSearch);
$('#dt-search').on('keypress', function(e) { if (e.which === 13) doSearch(); });
$('#dt-page-length').on('change', function() { table.page.len(parseInt(this.value)).draw(); });
$('#filter-status, #filter-payment').on('change', reloadTable);

function updateExportLink() {
    var params = new URLSearchParams(qsParams());
    $('#btn-export').attr('href', "{{ route('active-clients.exportExcel') }}?" + params.toString());
}
$('#filter-status, #filter-payment').on('change', updateExportLink);
updateExportLink();
</script>
@endsection

