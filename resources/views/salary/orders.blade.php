@extends('layouts.master')
@section('page_title')<i class="fa fa-money-check-dollar me-2" style="color:#8e44ad;"></i>ხელფასის ორდერები@endsection

@section('content')
<div style="padding:20px;">

<style>
.sal-header {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    background:#fff; border-radius:10px; padding:14px 20px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:18px;
}
.sal-header h2 { margin:0; font-size:18px; font-weight:700; color:#2c3e50; flex:1; }
.sal-controls { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.sal-controls select, .sal-controls input[type=month] {
    border:1.5px solid #e2e8f0; border-radius:6px; padding:5px 10px;
    font-size:13px; color:#2d3748; background:#fff;
}
.sal-table-wrap {
    background:#fff; border-radius:10px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); overflow:hidden;
}
.sal-table { width:100%; border-collapse:collapse; font-size:13px; }
.sal-table thead th {
    background:#8e44ad; color:#fff; font-weight:700; padding:8px 10px;
    white-space:nowrap; border-bottom:2px solid #7d3c98;
}
.sal-table tbody tr:nth-child(even) { background:#faf7fc; }
.sal-table tbody tr:hover { background:#f3e8ff; }
.sal-table td { padding:7px 10px; vertical-align:middle; border-bottom:1px solid #f0eaf7; }
.sal-table td.debt { color:#b91c1c; font-weight:700; background:#fee2e2; }
.sal-total td {
    background:#f0f4ff; font-weight:700; color:#2c3e50;
    border-top:2px solid #c3aed6; padding:8px 10px;
}
.sal-total td.label-cell { text-align:right; color:#8e44ad; }
.sal-badge {
    display:inline-block; padding:2px 8px; border-radius:10px;
    font-size:11px; font-weight:600;
}
.empty-state { text-align:center; padding:60px 20px; color:#aaa; }
.empty-state i { font-size:40px; margin-bottom:10px; display:block; color:#d8b4fe; }
.th-profit { background:#6c3483 !important; }
</style>

<div class="sal-header">
    <h2><i class="fa fa-money-check-dollar me-2" style="color:#8e44ad;"></i>ხელფასის ორდერები</h2>
    <div class="sal-controls">
        @if($isAdmin && $saleOperators->count())
        <select id="filterUser" onchange="applyFilter()">
            <option value="">ყველა გამყიდველი</option>
            @foreach($saleOperators as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
        @endif
        <input type="month" id="filterMonth" value="{{ $month }}" onchange="applyFilter()" style="font-size:14px;">
        <a id="excelBtn" href="{{ route('salary.orders.export', ['month' => $month, 'user_id' => $userId]) }}"
           class="btn btn-success btn-sm">
            <i class="fa fa-file-excel me-1"></i>Excel
        </a>
        <a href="{{ route('productsOut.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>უკან
        </a>
    </div>
</div>

<div class="sal-table-wrap">
@php
    $sumPrice   = 0;
    $sumDisc    = 0;
    $sumCost    = 0;
    $sumCourier = 0;
    $sumNet     = 0;
    $sumPaid    = 0;
    $sumDebt    = 0;
    foreach ($orders as $o) {
        $orig    = (float)$o->price_georgia;
        $disc    = (float)($o->discount ?? 0);
        $cost    = (float)($o->price_usa ?? 0);
        $courier = (float)($o->courier_price_tbilisi ?? 0)
                 + (float)($o->courier_price_region  ?? 0)
                 + (float)($o->courier_price_village ?? 0);
        $paid    = (float)($o->paid_tbc??0)+(float)($o->paid_bog??0)+(float)($o->paid_lib??0)+(float)($o->paid_cash??0);
        $net     = $orig - $disc - $cost - $courier;
        $debt    = max(0, ($orig - $disc) - $paid);
        $sumPrice   += $orig;
        $sumDisc    += $disc;
        $sumCost    += $cost;
        $sumCourier += $courier;
        $sumNet     += $net;
        $sumPaid    += $paid;
        $sumDebt    += $debt;
    }
    $extraCols = ($isAdmin && !$userId) ? 1 : 0; // გამყიდველი column
    $labelColspan = 6 + $extraCols; // # + (გამყ.) + პროდ. + ზომა + კლი. + ტელ + ქ-ი
@endphp

@if($orders->isEmpty())
<div class="empty-state">
    <i class="fa fa-box-open"></i>
    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}-ში გადახდილი ორდერები ვერ მოიძებნა
</div>
@else
<div style="overflow-x:auto;">
<table class="sal-table">
    <thead>
        <tr>
            <th>#</th>
            @if($isAdmin && !$userId)<th>გამყიდველი</th>@endif
            <th>პროდუქტი</th>
            <th>ზომა</th>
            <th>კლიენტი</th>
            <th>ტელ</th>
            <th>ქ-ი</th>
            <th>ფასი ₾</th>
            <th>ფასდ. ₾</th>
            <th>ფასი $</th>
            <th>საკ. ₾</th>
            <th class="th-profit">წმინდა ₾</th>
            <th>გადახდ. ₾</th>
            <th>ვალი ₾</th>
            <th>სტატუსი</th>
            <th>ორდ. #</th>
            <th>შექ. თარ.</th>
            <th>გადახდ. თარ.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($orders as $i => $o)
        @php
            $orig    = (float)$o->price_georgia;
            $disc    = (float)($o->discount ?? 0);
            $cost    = (float)($o->price_usa ?? 0);
            $courier = (float)($o->courier_price_tbilisi ?? 0)
                     + (float)($o->courier_price_region  ?? 0)
                     + (float)($o->courier_price_village ?? 0);
            $paid    = (float)($o->paid_tbc??0)+(float)($o->paid_bog??0)+(float)($o->paid_lib??0)+(float)($o->paid_cash??0);
            $net     = $orig - $disc - $cost - $courier;
            $debt    = max(0, ($orig - $disc) - $paid);
        @endphp
        <tr>
            <td style="color:#888;font-size:11px;">{{ $i + 1 }}</td>
            @if($isAdmin && !$userId)<td style="font-size:12px;color:#6c3483;">{{ $o->user?->name ?? '—' }}</td>@endif
            <td style="font-weight:600;">{{ $o->product?->name ?? '—' }}</td>
            <td>{{ $o->product_size ?? '' }}</td>
            <td>{{ $o->customer?->name ?? '—' }}</td>
            <td style="font-size:12px;color:#555;">{{ $o->order_alt_tel ?: ($o->customer?->tel ?? '') }}</td>
            <td style="font-size:12px;color:#555;">{{ $o->customer?->city?->name ?? '' }}</td>
            <td style="font-weight:600;">{{ number_format($orig, 2) }}</td>
            <td style="color:#8e44ad;">{{ $disc > 0 ? number_format($disc, 2) : '—' }}</td>
            <td style="color:#2980b9;font-weight:600;">{{ $cost > 0 ? '$'.number_format($cost, 2) : '—' }}</td>
            <td style="color:#e67e22;">{{ $courier > 0 ? number_format($courier, 2) : '—' }}</td>
            <td style="font-weight:700;color:{{ $net >= 0 ? '#16a085' : '#c0392b' }};background:{{ $net >= 0 ? '#f0fdf4' : '#fef2f2' }};">
                {{ number_format($net, 2) }}
            </td>
            <td style="color:#16a085;">{{ $paid > 0 ? number_format($paid, 2) : '—' }}</td>
            <td @class(['debt' => $debt > 0.01])>{{ $debt > 0.01 ? number_format($debt, 2) : '—' }}</td>
            <td>
                <span class="sal-badge" style="background:#{{ $o->orderStatus?->color === 'success' ? 'd1fae5;color:#065f46' : ($o->orderStatus?->color === 'danger' ? 'fee2e2;color:#b91c1c' : 'e0e7ff;color:#3730a3') }}">
                    {{ $o->orderStatus?->name ?? '—' }}
                </span>
            </td>
            <td style="font-family:monospace;font-size:12px;">{{ $o->order_number ?? ('S'.$o->id) }}</td>
            <td style="font-size:12px;color:#555;">{{ $o->created_at?->format('d.m.Y') }}</td>
            <td style="font-size:12px;font-weight:600;color:#8e44ad;">{{ $o->fully_paid_at?->format('d.m.Y') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr class="sal-total">
            <td class="label-cell" colspan="{{ $labelColspan }}" style="text-align:right;font-size:13px;">სულ:</td>
            <td>{{ number_format($sumPrice, 2) }}</td>
            <td style="color:#8e44ad;">{{ $sumDisc > 0 ? number_format($sumDisc, 2) : '—' }}</td>
            <td style="color:#2980b9;">{{ $sumCost > 0 ? '$'.number_format($sumCost, 2) : '—' }}</td>
            <td style="color:#e67e22;">{{ $sumCourier > 0 ? number_format($sumCourier, 2) : '—' }}</td>
            <td style="color:{{ $sumNet >= 0 ? '#16a085' : '#c0392b' }};">{{ number_format($sumNet, 2) }}</td>
            <td style="color:#16a085;">{{ number_format($sumPaid, 2) }}</td>
            <td @class(['debt' => $sumDebt > 0.01])>{{ $sumDebt > 0.01 ? number_format($sumDebt, 2) : '—' }}</td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
</table>
</div>
<div style="padding:10px 16px;font-size:12px;color:#888;">
    სულ {{ $orders->count() }} ორდერი ·
    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} ·
    <span style="color:#16a085;font-weight:600;">წმინდა სულ: {{ number_format($sumNet, 2) }} ₾</span>
</div>
@endif
</div>

</div>

<script>
function applyFilter() {
    var month  = document.getElementById('filterMonth').value;
    var userEl = document.getElementById('filterUser');
    var userId = userEl ? userEl.value : '';
    var url = '{{ route('salary.orders') }}?month=' + month + (userId ? '&user_id=' + userId : '');
    window.location.href = url;
}
document.getElementById('filterMonth').addEventListener('change', function() {
    var month  = this.value;
    var userEl = document.getElementById('filterUser');
    var userId = userEl ? userEl.value : '';
    document.getElementById('excelBtn').href =
        '{{ route('salary.orders.export') }}?month=' + month + (userId ? '&user_id=' + userId : '');
});
</script>
@endsection
