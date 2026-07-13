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
    position:sticky; top:0; z-index:10;
}
.sal-table tbody tr:nth-child(even) { background:#faf7fc; }
.sal-table tbody tr:hover { background:#f3e8ff; }
.sal-table td { padding:7px 10px; vertical-align:middle; border-bottom:1px solid #f0eaf7; }
.sal-table td.debt { color:#b91c1c; font-weight:700; background:#fee2e2; }
.sal-total td {
    background:#f0f4ff; font-weight:700; color:#2c3e50;
    border-top:2px solid #c3aed6; padding:8px 10px;
    position:sticky; bottom:0; z-index:9;
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
<div style="overflow:auto;max-height:calc(100vh - 220px);">
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
            <th id="status-th" style="cursor:pointer;user-select:none;" onclick="toggleStatusFilter(event)">
                სტატუსი <i class="fa fa-filter" id="status-filter-icon" style="font-size:10px;opacity:.7;"></i>
            </th>
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
        <tr class="sal-row"
            data-status="{{ $o->orderStatus?->name ?? '' }}"
            data-price="{{ $orig }}"
            data-disc="{{ $disc }}"
            data-cost="{{ $cost }}"
            data-courier="{{ $courier }}"
            data-net="{{ $net }}"
            data-paid="{{ $paid }}"
            data-debt="{{ $debt }}">
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
            <td id="sum-price">{{ number_format($sumPrice, 2) }}</td>
            <td id="sum-disc" style="color:#8e44ad;">{{ $sumDisc > 0 ? number_format($sumDisc, 2) : '—' }}</td>
            <td id="sum-cost" style="color:#2980b9;">{{ $sumCost > 0 ? '$'.number_format($sumCost, 2) : '—' }}</td>
            <td id="sum-courier" style="color:#e67e22;">{{ $sumCourier > 0 ? number_format($sumCourier, 2) : '—' }}</td>
            <td id="sum-net">{{ number_format($sumNet, 2) }}</td>
            <td id="sum-paid" style="color:#16a085;">{{ number_format($sumPaid, 2) }}</td>
            <td id="sum-debt" @class(['debt' => $sumDebt > 0.01])>{{ $sumDebt > 0.01 ? number_format($sumDebt, 2) : '—' }}</td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
</table>
</div>
<div style="padding:10px 16px;font-size:12px;color:#888;">
    <span id="visible-count">{{ $orders->count() }}</span> ორდერი ·
    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} ·
    <span id="footer-net" style="color:#16a085;font-weight:600;">წმინდა სულ: {{ number_format($sumNet, 2) }} ₾</span>
</div>
@endif
</div>

</div>

{{-- Status filter dropdown — body-ზე mount-ი z-index პრობლემის გარეშე --}}
<div id="status-filter-dropdown" style="display:none;position:fixed;z-index:99999;background:#fff;color:#2c3e50;border:1.5px solid #c3aed6;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.18);min-width:180px;padding:8px 0;">
    <div style="padding:4px 12px 6px;border-bottom:1px solid #f0eaf7;">
        <label style="font-size:12px;font-weight:600;color:#8e44ad;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <input type="checkbox" id="status-all" checked onchange="statusFilterAll(this)"> ყველა
        </label>
    </div>
    <div id="status-filter-list" style="max-height:240px;overflow-y:auto;padding:4px 0;color:#2c3e50;"></div>
    <div style="padding:6px 12px 2px;border-top:1px solid #f0eaf7;">
        <button onclick="applyStatusFilter()" class="btn btn-sm" style="width:100%;background:#8e44ad;color:#fff;font-size:12px;padding:4px 0;">გამოყენება</button>
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

// ── Status filter ─────────────────────────────────────────────
(function() {
    var statuses = {};
    document.querySelectorAll('.sal-row').forEach(function(tr) {
        var s = tr.dataset.status;
        if (s) statuses[s] = true;
    });
    var list = document.getElementById('status-filter-list');
    Object.keys(statuses).sort().forEach(function(s) {
        var label = document.createElement('label');
        label.style.cssText = 'display:flex;align-items:center;gap:6px;padding:5px 12px;font-size:13px;cursor:pointer;color:#2c3e50;white-space:nowrap;';
        var cb = document.createElement('input');
        cb.type = 'checkbox'; cb.className = 'status-cb'; cb.value = s; cb.checked = true;
        cb.addEventListener('change', syncAllCheckbox);
        label.appendChild(cb);
        label.appendChild(document.createTextNode(s));
        list.appendChild(label);
    });
    function syncAllCheckbox() {
        var cbs = document.querySelectorAll('.status-cb');
        document.getElementById('status-all').checked = Array.from(cbs).every(function(cb){ return cb.checked; });
    }
})();

window.statusFilterAll = function(el) {
    document.querySelectorAll('.status-cb').forEach(function(cb){ cb.checked = el.checked; });
};

window.toggleStatusFilter = function(e) {
    e.stopPropagation();
    var dd  = document.getElementById('status-filter-dropdown');
    var th  = document.getElementById('status-th');
    var rect = th.getBoundingClientRect();
    if (dd.style.display === 'none') {
        dd.style.top  = (rect.bottom + 2) + 'px';
        dd.style.left = rect.left + 'px';
        dd.style.display = 'block';
    } else {
        dd.style.display = 'none';
    }
};

document.addEventListener('click', function(e) {
    var dd = document.getElementById('status-filter-dropdown');
    var th = document.getElementById('status-th');
    if (dd && !dd.contains(e.target) && e.target !== th && !th.contains(e.target)) {
        dd.style.display = 'none';
    }
});

function fmt(v) { return v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

window.applyStatusFilter = function() {
    var selected = new Set();
    document.querySelectorAll('.status-cb:checked').forEach(function(cb){ selected.add(cb.value); });
    var allChecked = document.getElementById('status-all').checked;

    var sPrice=0, sDisc=0, sCost=0, sCourier=0, sNet=0, sPaid=0, sDebt=0, cnt=0;

    document.querySelectorAll('.sal-row').forEach(function(tr) {
        var show = allChecked || selected.has(tr.dataset.status);
        tr.style.display = show ? '' : 'none';
        if (show) {
            sPrice   += parseFloat(tr.dataset.price   || 0);
            sDisc    += parseFloat(tr.dataset.disc    || 0);
            sCost    += parseFloat(tr.dataset.cost    || 0);
            sCourier += parseFloat(tr.dataset.courier || 0);
            sNet     += parseFloat(tr.dataset.net     || 0);
            sPaid    += parseFloat(tr.dataset.paid    || 0);
            sDebt    += parseFloat(tr.dataset.debt    || 0);
            cnt++;
        }
    });

    document.getElementById('sum-price').textContent   = fmt(sPrice);
    document.getElementById('sum-disc').textContent    = sDisc > 0 ? fmt(sDisc) : '—';
    document.getElementById('sum-cost').textContent    = sCost > 0 ? '$'+fmt(sCost) : '—';
    document.getElementById('sum-courier').textContent = sCourier > 0 ? fmt(sCourier) : '—';

    var netEl = document.getElementById('sum-net');
    netEl.textContent = fmt(sNet);
    netEl.style.color = sNet >= 0 ? '#16a085' : '#c0392b';

    document.getElementById('sum-paid').textContent = fmt(sPaid);

    var debtEl = document.getElementById('sum-debt');
    debtEl.textContent = sDebt > 0.01 ? fmt(sDebt) : '—';
    debtEl.className   = sDebt > 0.01 ? 'debt' : '';

    document.getElementById('visible-count').textContent = cnt;
    var footNet = document.getElementById('footer-net');
    footNet.textContent = 'წმინდა სულ: ' + fmt(sNet) + ' ₾';
    footNet.style.color = sNet >= 0 ? '#16a085' : '#c0392b';

    var icon = document.getElementById('status-filter-icon');
    icon.style.color   = allChecked ? '' : '#f39c12';
    icon.style.opacity = allChecked ? '.7' : '1';

    document.getElementById('status-filter-dropdown').style.display = 'none';
};
</script>
@endsection
