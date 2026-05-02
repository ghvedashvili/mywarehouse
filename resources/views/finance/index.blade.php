@extends('layouts.master')
@section('page_title')<i class="fa fa-chart-line me-2" style="color:#16a085;"></i>ფინანსები@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════════
     FINANCE DASHBOARD
     შემოსავალი / გასავალი / მოგება
══════════════════════════════════════════════════════════════ --}}

<style>
:root {
    --green:  #00b894;
    --red:    #d63031;
    --blue:   #0984e3;
    --orange: #e17055;
    --purple: #6c5ce7;
    --gray:   #636e72;
    --light:  #f8f9fa;
    --card-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.fin-wrap        { padding: 20px; font-family: 'Segoe UI', sans-serif; }
.fin-title       { font-size: 22px; font-weight: 700; color: #2d3436; margin-bottom: 20px; }
.fin-title span  { color: var(--green); }

/* ─── PERIOD FILTER ───────────────────────────────────────────── */
.period-bar {
    background: #fff;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.period-bar .btn-period {
    padding: 5px 14px;
    border-radius: 20px;
    border: 1.5px solid #dfe6e9;
    background: #fff;
    font-size: 12px;
    font-weight: 600;
    color: #636e72;
    cursor: pointer;
    transition: all .2s;
}
.period-bar .btn-period.active,
.period-bar .btn-period:hover {
    background: var(--green);
    border-color: var(--green);
    color: #fff;
}
.period-bar .custom-dates {
    display: none;
    gap: 6px;
    align-items: center;
}
.period-bar .custom-dates.show { display: flex; }
.period-bar input[type=date] {
    border: 1.5px solid #dfe6e9;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    color: #2d3436;
}
.period-bar .btn-apply {
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

/* ─── KPI CARDS ───────────────────────────────────────────────── */
.kpi-row        { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
.kpi-card {
    flex: 1;
    min-width: 150px;
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px; height: 100%;
    background: var(--accent, var(--green));
}
.kpi-card .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase;
                        color: #b2bec3; letter-spacing: .5px; margin-bottom: 6px; }
.kpi-card .kpi-value { font-size: 22px; font-weight: 800; color: #2d3436; }
.kpi-card .kpi-sub   { font-size: 11px; color: #b2bec3; margin-top: 3px; }
.kpi-card.profit-positive { --accent: var(--green); }
.kpi-card.profit-negative { --accent: var(--red); }
.kpi-card.profit-positive .kpi-value { color: var(--green); }
.kpi-card.profit-negative .kpi-value { color: var(--red); }

/* ─── EXPANDABLE KPI CARD ─────────────────────────────────────── */
.kpi-expandable {
    cursor: pointer;
    transition: box-shadow .2s;
}
.kpi-expandable:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.13); }

.kpi-expand-arrow {
    font-size: 13px;
    color: #b2bec3;
    transition: transform .3s ease;
    user-select: none;
    margin-left: 8px;
}
.kpi-expandable.expanded .kpi-expand-arrow { transform: rotate(180deg); }

.kpi-details {
    max-height: 0;
    overflow: hidden;
    transition: max-height .35s ease, opacity .25s ease;
    opacity: 0;
}
.kpi-expandable.expanded .kpi-details {
    max-height: 200px;
    opacity: 1;
}

.kpi-details-inner {
    border-top: 1px solid #f0f0f0;
    margin-top: 10px;
    padding-top: 8px;
}
.kpi-detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    padding: 3px 0;
    color: #636e72;
}
.kpi-detail-row.net-row {
    border-top: 1px dashed #dfe6e9;
    margin-top: 4px;
    padding-top: 5px;
    font-weight: 700;
    font-size: 12px;
    color: #2d3436;
}

/* ─── MIDDLE SECTION ──────────────────────────────────────────── */
.mid-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.mid-row .col-chart { flex: 2; min-width: 280px; }
.mid-row .col-breakdown { flex: 1; min-width: 220px; }

.fin-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: var(--card-shadow);
    height: 100%;
}
.fin-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #636e72;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

/* ─── BREAKDOWN BARS ──────────────────────────────────────────── */
.breakdown-item { margin-bottom: 12px; }
.breakdown-item .bd-top {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 4px;
}
.breakdown-item .bd-label { font-weight: 600; color: #2d3436; }
.breakdown-item .bd-val   { font-weight: 700; color: var(--red); }
.breakdown-bar { height: 6px; background: #f0f0f0; border-radius: 4px; overflow: hidden; }
.breakdown-bar-fill { height: 100%; border-radius: 4px; background: var(--red); transition: width .6s ease; }

/* cost summary */
.cost-summary {
    background: #fff8f8;
    border-radius: 8px;
    padding: 10px 14px;
    margin-top: 14px;
    font-size: 12px;
}
.cost-row { display: flex; justify-content: space-between; padding: 3px 0; }
.cost-row .lbl { color: #636e72; }
.cost-row .val { font-weight: 700; }
.cost-row.total-row { border-top: 1px solid #ffe0e0; margin-top: 4px; padding-top: 6px; }
.cost-row.total-row .lbl,
.cost-row.total-row .val { color: var(--red); font-size: 13px; }

/* ─── ENTRIES TABLE ───────────────────────────────────────────── */
.entries-section { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: var(--card-shadow); margin-bottom: 20px; }
.entries-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.entries-header .title { font-size: 14px; font-weight: 700; color: #2d3436; }
.btn-add-entry {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 16px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.entries-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.entries-table th {
    background: #f8f9fa;
    padding: 8px 12px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #b2bec3;
    letter-spacing: .4px;
}
.entries-table td { padding: 9px 12px; border-bottom: 1px solid #f5f5f5; }
.entries-table tr:hover td { background: #fafafa; }
.badge-type {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.badge-income  { background: #d4efdf; color: #1e8449; }
.badge-expense { background: #fadbd8; color: #a93226; }
.badge-cat {
    background: #eaf4fb;
    color: #1a5276;
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
}
.btn-del {
    background: none;
    border: none;
    color: #e74c3c;
    font-size: 14px;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background .2s;
}
.btn-del:hover { background: #fadbd8; }

/* ─── MODAL ───────────────────────────────────────────────────── */
.fin-modal-header { background: #f8f9fa; }
</style>

<div class="mod-wrap">
<div class="fin-wrap" style="padding:0;">

    <div class="mod-header" style="margin-bottom:16px;">
        <div>
            <h2 class="mod-title"><i class="fa fa-chart-line me-2" style="color:#16a085;"></i>ფინანსების დაშბორდი</h2>
            <p class="mod-subtitle">შემოსავლები, გასავლები და მოგება</p>
        </div>
    </div>

    {{-- ══ PERIOD FILTER ══ --}}
    <div class="period-bar" id="periodBar">
        @php $activePeriod = request()->input('period', 'month'); @endphp
        @foreach([
            'today'   => 'დღეს',
            'week'    => 'კვირა',
            'month'   => 'თვე',
            'quarter' => 'კვარტალი',
            'year'    => 'წელი',
            'custom'  => 'Custom',
        ] as $key => $label)
            <button class="btn-period {{ $activePeriod === $key ? 'active' : '' }}"
                    data-period="{{ $key }}">{{ $label }}</button>
        @endforeach

        <div class="custom-dates {{ $activePeriod === 'custom' ? 'show' : '' }}" id="customDates">
            <input type="date" id="dateFrom" value="{{ $from }}">
            <span style="color:#b2bec3;">—</span>
            <input type="date" id="dateTo" value="{{ $to }}">
            <button class="btn-apply" id="applyCustom">გამოყენება</button>
        </div>
    </div>

    {{-- ══ KPI CARDS ══ --}}
    <div class="kpi-row" id="kpiRow">
        <div class="kpi-card" style="--accent: var(--green);">
            <div class="kpi-label">📦 გაყიდვები</div>
            <div class="kpi-value" id="kpi-sale-count">{{ $stats['sale_count'] }}</div>
            <div class="kpi-sub">შექმნილი ორდერი</div>
        </div>
        <div class="kpi-card" style="--accent: var(--orange);">
            <div class="kpi-label">↩ დაბრუნება</div>
            <div class="kpi-value" id="kpi-return-count">{{ $stats['return_count'] }}</div>
            <div class="kpi-sub" id="kpi-return-amount" style="color:var(--red);">-{{ number_format($stats['return_amount'],2) }} ₾</div>
        </div>
        <div class="kpi-card" style="--accent: var(--purple);">
            <div class="kpi-label">🔄 გაცვლა</div>
            <div class="kpi-value" id="kpi-change-count">{{ $stats['change_count'] }}</div>
            <div class="kpi-sub">ჩათვლილია შემოსავალში</div>
        </div>
        <div class="kpi-card kpi-expandable" style="--accent: var(--blue);" onclick="toggleKpiCard(this)">
            <div class="kpi-label">💵 შემოსავალი (სულ)</div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="kpi-value" id="kpi-total-rev">{{ number_format($stats['total_revenue'],2) }} ₾</div>
                <div class="kpi-expand-arrow">▼</div>
            </div>
            <div class="kpi-sub">სუფთა შემოსავალი</div>
            <div class="kpi-details">
                <div class="kpi-details-inner">
                    <div class="kpi-detail-row">
                        <span>📦 გაყიდვები</span>
                        <span id="kpi-rev-d-gross">{{ number_format($stats['gross_revenue'],2) }} ₾</span>
                    </div>
                    @if($stats['return_amount'] > 0)
                    <div class="kpi-detail-row" style="color:var(--red);">
                        <span>↩ დაბრუნება</span>
                        <span id="kpi-rev-d-ret">-{{ number_format($stats['return_amount'],2) }} ₾</span>
                    </div>
                    @endif
                    @if($stats['extra_income'] > 0)
                    <div class="kpi-detail-row" style="color:var(--green);">
                        <span>➕ დამატებითი</span>
                        <span id="kpi-rev-d-extra">{{ number_format($stats['extra_income'],2) }} ₾</span>
                    </div>
                    @endif
                    <div class="kpi-detail-row net-row">
                        <span>სუფთა</span>
                        <span>{{ number_format($stats['total_revenue'],2) }} ₾</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="kpi-card kpi-expandable" style="--accent: var(--red);" onclick="toggleKpiCard(this)">
            <div class="kpi-label">📤 გასავალი (სულ)</div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="kpi-value" id="kpi-total-cost">{{ number_format($stats['total_expenses'],2) }} ₾</div>
                <div class="kpi-expand-arrow">▼</div>
            </div>
            <div class="kpi-sub">საკურიერო + სხვა ხარჯები</div>
            <div class="kpi-details">
                <div class="kpi-details-inner">
                    <div class="kpi-detail-row">
                        <span>🚚 საკურიერო (სუფთა)</span>
                        <span id="kpi-cost-d-courier">{{ number_format($stats['net_courier'],2) }} ₾</span>
                    </div>
                    @if($stats['extra_expense'] > 0)
                    <div class="kpi-detail-row">
                        <span>📋 სხვა ხარჯები</span>
                        <span id="kpi-cost-d-extra">{{ number_format($stats['extra_expense'],2) }} ₾</span>
                    </div>
                    @endif
                    <div class="kpi-detail-row net-row">
                        <span>სულ</span>
                        <span id="kpi-cost-net-total">{{ number_format($stats['total_expenses'],2) }} ₾</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="kpi-card {{ $stats['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}" id="kpi-profit-card">
            <div class="kpi-label">{{ $stats['profit'] >= 0 ? '📈 მოგება' : '📉 წაგება' }}</div>
            <div class="kpi-value" id="kpi-profit">{{ number_format($stats['profit'],2) }} ₾</div>
            <div class="kpi-sub" id="kpi-margin">მარჟა: {{ $stats['profit_margin'] }}%</div>
        </div>
        <div class="kpi-card" style="--accent: #e17055;">
            <div class="kpi-label">⚠️ მომხმ. დავალიანება</div>
            <div class="kpi-value" id="kpi-customer-debt"
                 style="color:{{ $stats['customer_debt'] > 0 ? '#d63031' : '#00b894' }};">
                {{ number_format($stats['customer_debt'],2) }} ₾
            </div>
            <div class="kpi-sub">ყველა გადაუხდელი ორდერი</div>
        </div>
    </div>

    {{-- ══ CHART + COST BREAKDOWN ══ --}}
    <div class="mid-row">

        {{-- Trend Chart --}}
        <div class="col-chart">
            <div class="fin-card">
                <div class="fin-card-title">📊 ბოლო 6 თვის ტენდენცია</div>
                <canvas id="trendChart" height="200"></canvas>
            </div>
        </div>

        {{-- Expense Breakdown --}}
        <div class="col-breakdown">
            <div class="fin-card">
                <div class="fin-card-title">🧮 გასავლის დაშლა</div>

                <div id="breakdownList">
                    @php
                        $catLabels     = \App\Models\FinanceEntry::$categoryLabels;
                        $totalExpenses = max($stats['total_expenses'], 0.01);
                    @endphp

                    {{-- courier (net) --}}
                    @php $pct = max(0, round($stats['net_courier'] / $totalExpenses * 100)); @endphp
                    <div class="breakdown-item">
                        <div class="bd-top">
                            <span class="bd-label">🚚 საკურიერო (სუფთა)</span>
                            <span class="bd-val" id="bd-courier-total">{{ number_format($stats['net_courier'],2) }} ₾</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%; background: var(--purple);"></div></div>
                        <div style="font-size:11px; color:#636e72; margin-top:3px; padding-left:2px;">
                            └ 📤 გამოგზ.: <strong id="bd-gross-courier">{{ number_format($stats['gross_courier'],2) }} ₾</strong>
                        </div>
                        @if($stats['courier_refund_total'] > 0)
                        <div style="font-size:11px; color:var(--green); margin-top:2px; padding-left:2px;">
                            └ ↩ კლ. დაბ.: <strong id="bd-courier-refund">-{{ number_format($stats['courier_refund_total'],2) }} ₾</strong>
                        </div>
                        @endif
                    </div>

                    {{-- extra expenses by category --}}
                    @foreach($stats['expense_by_category'] as $cat => $amount)
                        @php $pct = max(0, round($amount / $totalExpenses * 100)); @endphp
                        <div class="breakdown-item">
                            <div class="bd-top">
                                <span class="bd-label">{{ $catLabels[$cat] ?? $cat }}</span>
                                <span class="bd-val">{{ number_format($amount,2) }} ₾</span>
                            </div>
                            <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%;"></div></div>
                        </div>
                    @endforeach
                </div>

                <div class="cost-summary">
                    <div class="cost-row">
                        <span class="lbl">🚚 საკურიერო (გამოგზ.)</span>
                        <span class="val" id="cs-gross-courier">{{ number_format($stats['gross_courier'],2) }} ₾</span>
                    </div>
                    @if($stats['courier_refund_total'] > 0)
                    <div class="cost-row" style="padding-left:12px; opacity:.8;">
                        <span class="lbl" style="font-size:11px; color:var(--green);">└ ↩ კლ. დაბრ.</span>
                        <span class="val" id="cs-courier-refund" style="color:var(--green); font-size:11px;">-{{ number_format($stats['courier_refund_total'],2) }} ₾</span>
                    </div>
                    @endif
                    <div class="cost-row">
                        <span class="lbl">🚚 საკურიერო (სუფთა)</span>
                        <span class="val" id="cs-courier">{{ number_format($stats['net_courier'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">📋 სხვა ხარჯები</span>
                        <span class="val" id="cs-extra">{{ number_format($stats['extra_expense'],2) }} ₾</span>
                    </div>
                    <div class="cost-row total-row">
                        <span class="lbl">სულ გასავალი</span>
                        <span class="val" id="cs-total">{{ number_format($stats['total_expenses'],2) }} ₾</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revenue Breakdown --}}
        <div class="col-breakdown">
            <div class="fin-card">
                <div class="fin-card-title">💰 შემოსავლის დაშლა</div>

                @php
                    $totalRev  = max($stats['total_revenue'], 0.01);
                    $revItems  = [
                        'tbc'  => ['TBC',   $stats['paid_tbc_total'],  '#0984e3'],
                        'bog'  => ['BOG',   $stats['paid_bog_total'],  '#e17055'],
                        'lib'  => ['LIB',   $stats['paid_lib_total'],  '#00b894'],
                        'cash' => ['ნაღდი', $stats['paid_cash_total'], '#6c5ce7'],
                    ];
                @endphp

                <div id="revenueBreakdownList">
                    @foreach($revItems as $key => [$label, $amount, $color])
                    @php $pct = max(0, round($amount / $totalRev * 100)); @endphp
                    <div class="breakdown-item">
                        <div class="bd-top">
                            <span class="bd-label">💳 {{ $label }}</span>
                            <span class="bd-val" style="color:{{ $color }};" id="bd-rev-{{ $key }}">{{ number_format($amount,2) }} ₾</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%; background:{{ $color }};"></div></div>
                    </div>
                    @endforeach

                    @if($stats['extra_income'] > 0)
                    @php $pct = max(0, round($stats['extra_income'] / $totalRev * 100)); @endphp
                    <div class="breakdown-item">
                        <div class="bd-top">
                            <span class="bd-label">➕ სხვა შემოსავ.</span>
                            <span class="bd-val" style="color:var(--green);" id="bd-rev-extra">{{ number_format($stats['extra_income'],2) }} ₾</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%; background:var(--green);"></div></div>
                    </div>
                    @endif
                </div>

                <div class="cost-summary" style="background:#f0fff8;">
                    <div class="cost-row">
                        <span class="lbl">💳 TBC</span>
                        <span class="val" id="cs-rev-tbc" style="color:#0984e3;">{{ number_format($stats['paid_tbc_total'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">💳 BOG</span>
                        <span class="val" id="cs-rev-bog" style="color:var(--orange);">{{ number_format($stats['paid_bog_total'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">💳 LIB</span>
                        <span class="val" id="cs-rev-lib" style="color:var(--green);">{{ number_format($stats['paid_lib_total'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">💵 ნაღდი</span>
                        <span class="val" id="cs-rev-cash" style="color:var(--purple);">{{ number_format($stats['paid_cash_total'],2) }} ₾</span>
                    </div>
                    @if($stats['extra_income'] > 0)
                    <div class="cost-row">
                        <span class="lbl">➕ სხვა</span>
                        <span class="val" id="cs-rev-extra" style="color:var(--green);">{{ number_format($stats['extra_income'],2) }} ₾</span>
                    </div>
                    @endif
                    <div class="cost-row total-row" style="border-top-color:#c3f0d8;">
                        <span class="lbl" style="color:var(--green);">სულ შემოსავალი</span>
                        <span class="val" id="cs-rev-total" style="color:var(--green);">{{ number_format($stats['total_revenue'],2) }} ₾</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ SALARY SECTION ══ --}}
    @if(auth()->user()->role === 'admin')
    <div class="entries-section" id="salary-section">
        <div class="entries-header">
            <div class="title">👤 თანამშრომელთა ხელფასები</div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="month" id="salary-month" value="{{ now()->format('Y-m') }}"
                       style="border:1.5px solid #dfe6e9; border-radius:6px; padding:4px 10px; font-size:13px;">
                <button class="btn-add-entry" onclick="loadSalary()" style="background:var(--blue);">
                    <i class="fa fa-calculator"></i> გათვლა
                </button>
            </div>
        </div>

        <div id="salary-result" style="display:none;">

            {{-- Sale Operators --}}
            <div style="margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:#636e72;
                            letter-spacing:.5px; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #f0f0f0;">
                    💼 Sale Operators
                </div>
                <div class="table-responsive">
                <table class="entries-table" id="salary-sale-table" style="min-width:600px;">
                    <thead>
                        <tr>
                            <th>სახელი</th>
                            <th>ორდერები</th>
                            <th>ბაზა (×3₾)</th>
                            <th>ბონუსი (1%)</th>
                            <th>გამოქვ.</th>
                            <th>სულ</th>
                            <th>ჩანიშვნა</th>
                            <th>სტატუსი</th>
                        </tr>
                    </thead>
                    <tbody id="salary-sale-body"></tbody>
                </table>
                </div>
            </div>

            {{-- Warehouse Operators --}}
            <div style="margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:#636e72;
                            letter-spacing:.5px; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #f0f0f0;">
                    🏭 Warehouse Operators
                </div>
                <div class="table-responsive">
                <table class="entries-table" id="salary-wh-table" style="min-width:500px;">
                    <thead>
                        <tr>
                            <th>სახელი</th>
                            <th>ყველა ორდ.</th>
                            <th>გათვლილი</th>
                            <th>ხელით თანხა</th>
                            <th>ჩანიშვნა</th>
                            <th>სტატუსი</th>
                        </tr>
                    </thead>
                    <tbody id="salary-wh-body"></tbody>
                </table>
                </div>
            </div>

            {{-- Admins --}}
            <div style="margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:#636e72;
                            letter-spacing:.5px; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid #f0f0f0;">
                    🔑 Admins
                </div>
                <div class="table-responsive">
                <table class="entries-table" style="min-width:400px;">
                    <thead>
                        <tr><th>სახელი</th><th>ხელით თანხა</th><th>ჩანიშვნა</th><th>სტატუსი</th></tr>
                    </thead>
                    <tbody id="salary-admin-body"></tbody>
                </table>
                </div>
            </div>

            <div style="text-align:right; margin-top:10px;">
                <button class="btn-add-entry" onclick="recordSalaries()" id="btn-record-salary">
                    <i class="fa fa-save"></i> ხელფასების ჩაფიქსირება
                </button>
            </div>
        </div>

        <div id="salary-loading" style="display:none; text-align:center; padding:20px; color:#b2bec3;">
            <i class="fa fa-spinner fa-spin"></i> გათვლა...
        </div>
    </div>
    @endif

    {{-- ══ ENTRIES TABLE ══ --}}
    <div class="entries-section">
        <div class="entries-header">
            <div class="title">📋 დამატებითი ჩანაწერები</div>
            @if(auth()->user()->role === 'admin')
            <button class="btn-add-entry" data-bs-toggle="modal" data-bs-target="#modal-entry">
                + ჩანაწერის დამატება
            </button>
            @endif
        </div>

        <div class="table-responsive">
        <table class="entries-table" style="min-width:500px;">
            <thead>
                <tr>
                    <th>თარიღი</th>
                    <th>ტიპი</th>
                    <th>კატეგორია</th>
                    <th>აღწერა</th>
                    <th>თანხა</th>
                    @if(auth()->user()->role === 'admin')
                    <th></th>
                    @endif
                </tr>
            </thead>
            <tbody id="entriesBody">
                @forelse($entries as $entry)
                <tr id="entry-row-{{ $entry->id }}">
                    <td>{{ $entry->entry_date->format('d.m.Y') }}</td>
                    <td>
                        <span class="badge-type {{ $entry->type === 'income' ? 'badge-income' : 'badge-expense' }}">
                            {{ $entry->type === 'income' ? 'შემოსავ.' : 'გასავ.' }}
                        </span>
                    </td>
                    <td><span class="badge-cat">{{ $entry->category_label }}</span></td>
                    <td style="color:#636e72;">{{ $entry->description ?: '—' }}</td>
                    <td style="font-weight:700; color: {{ $entry->type === 'income' ? 'var(--green)' : 'var(--red)' }};">
                        {{ $entry->type === 'expense' ? '-' : '+' }}{{ number_format($entry->amount, 2) }} ₾
                    </td>
                    @if(auth()->user()->role === 'admin')
                    <td>
                        <button class="btn-del" onclick="deleteEntry({{ $entry->id }})">🗑</button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color:#b2bec3; padding:30px;">ჩანაწერები არ არის</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>{{-- /table-responsive --}}
    </div>

</div>{{-- /fin-wrap --}}
</div>{{-- /mod-wrap --}}

{{-- ══ MODAL: ADD ENTRY ══ --}}
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="modal-entry" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius:10px;">
            <form id="form-entry">
                @csrf
                <div class="modal-header fin-modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h4 class="modal-title" style="font-weight:700;">+ ჩანაწერის დამატება</h4>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">ტიპი</label>
                                <select name="type" id="entry-type" class="form-control" required>
                                    <option value="expense">📤 გასავ. (ხარჯი)</option>
                                    <option value="income">📥 შემოსავ.</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">კატეგორია</label>
                                <select name="category" class="form-control" required>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">თანხა (₾)</label>
                                <input type="number" name="amount" class="form-control"
                                       step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">თარიღი</label>
                                <input type="date" name="entry_date" class="form-control"
                                       value="{{ now()->toDateString() }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:600;">აღწერა <small style="color:#b2bec3;">(სურვილისამებრ)</small></label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="მაგ: მაისის ხელფასი, ოფისის ქირა..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-success" id="btn-save-entry">
                        <i class="fa fa-save"></i> შენახვა
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@section('bot')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// ══════════════════════════════════════════════════════════
// STATE
// ══════════════════════════════════════════════════════════
let currentPeriod = '{{ request()->input("period","month") }}';
let currentFrom   = '{{ $from }}';
let currentTo     = '{{ $to }}';

// ══════════════════════════════════════════════════════════
// PERIOD BUTTONS
// ══════════════════════════════════════════════════════════
document.querySelectorAll('.btn-period').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.btn-period').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        currentPeriod = this.dataset.period;
        const customDates = document.getElementById('customDates');

        if (currentPeriod === 'custom') {
            customDates.classList.add('show');
        } else {
            customDates.classList.remove('show');
            fetchStats();
        }
    });
});

document.getElementById('applyCustom')?.addEventListener('click', () => {
    currentFrom = document.getElementById('dateFrom').value;
    currentTo   = document.getElementById('dateTo').value;
    fetchStats();
});

// ══════════════════════════════════════════════════════════
// FETCH STATS (AJAX)
// ══════════════════════════════════════════════════════════
function fetchStats() {
    const params = new URLSearchParams({ period: currentPeriod });
    if (currentPeriod === 'custom') {
        params.set('from', currentFrom);
        params.set('to',   currentTo);
    }

    fetch(`{{ route('finance.apiStats') }}?${params}`)
        .then(r => r.json())
        .then(s => updateUI(s));
}

function fmt(n) {
    return parseFloat(n).toLocaleString('ka-GE', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₾';
}

function setOrHide(id, value, prefix, color) {
    const el = document.getElementById(id);
    if (!el) return;
    const row = el.closest('.kpi-detail-row');
    if (value > 0) {
        el.textContent = (prefix || '') + fmt(value) ;
        if (color) el.style.color = color;
        if (row) row.style.display = '';
    } else {
        if (row) row.style.display = 'none';
    }
}

function updateUI(s) {
    document.getElementById('kpi-sale-count').textContent   = s.sale_count;
    document.getElementById('kpi-return-count').textContent  = s.return_count;
    document.getElementById('kpi-return-amount').textContent = '-' + fmt(s.return_amount);
    document.getElementById('kpi-change-count').textContent  = s.change_count;

    // ── შემოსავალი ──────────────────────────────────────────────────
    document.getElementById('kpi-total-rev').textContent = fmt(s.total_revenue);
    setOrHide('kpi-rev-d-gross', s.gross_revenue);
    setOrHide('kpi-rev-d-ret',   s.return_amount, '-', 'var(--red)');
    setOrHide('kpi-rev-d-extra', s.extra_income,  '',  'var(--green)');
    const revNet = document.getElementById('kpi-total-rev')?.closest('.kpi-expandable')
                           ?.querySelector('.net-row span:last-child');
    if (revNet) revNet.textContent = fmt(s.total_revenue);

    // ── გასავალი (total_expenses = courier + other) ─────────────────
    document.getElementById('kpi-total-cost').textContent = fmt(s.total_expenses);
    setOrHide('kpi-cost-d-courier', s.net_courier);
    setOrHide('kpi-cost-d-extra',   s.extra_expense);
    const kpiCostNetEl = document.getElementById('kpi-cost-net-total');
    if (kpiCostNetEl) kpiCostNetEl.textContent = fmt(s.total_expenses);

    document.getElementById('kpi-profit').textContent = fmt(s.profit);
    document.getElementById('kpi-margin').textContent = 'მარჟა: ' + s.profit_margin + '%';

    const debtEl = document.getElementById('kpi-customer-debt');
    if (debtEl) {
        debtEl.textContent = fmt(s.customer_debt);
        debtEl.style.color = s.customer_debt > 0 ? 'var(--red)' : 'var(--green)';
    }

    const profitCard = document.getElementById('kpi-profit-card');
    profitCard.classList.toggle('profit-positive', s.profit >= 0);
    profitCard.classList.toggle('profit-negative', s.profit < 0);
    profitCard.querySelector('.kpi-label').textContent = s.profit >= 0 ? '📈 მოგება' : '📉 წაგება';

    // ── expense breakdown panel ──────────────────────────────────────
    const bdGross = document.getElementById('bd-gross-courier');
    if (bdGross) bdGross.textContent = fmt(s.gross_courier);

    document.getElementById('bd-courier-total').textContent = fmt(s.net_courier);

    const bdRef = document.getElementById('bd-courier-refund');
    if (bdRef) {
        bdRef.textContent = '-' + fmt(s.courier_refund_total);
        bdRef.closest('div').style.display = s.courier_refund_total > 0 ? '' : 'none';
    }

    const csGross = document.getElementById('cs-gross-courier');
    if (csGross) csGross.textContent = fmt(s.gross_courier);

    document.getElementById('cs-courier').textContent = fmt(s.net_courier);

    const csRef = document.getElementById('cs-courier-refund');
    if (csRef) {
        csRef.textContent = '-' + fmt(s.courier_refund_total);
        csRef.closest('.cost-row').style.display = s.courier_refund_total > 0 ? '' : 'none';
    }

    document.getElementById('cs-extra').textContent = fmt(s.extra_expense);
    document.getElementById('cs-total').textContent = fmt(s.total_expenses);

    // ── revenue breakdown panel ──────────────────────────────────────
    const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = fmt(val); };
    setEl('cs-rev-tbc',   s.paid_tbc_total);
    setEl('cs-rev-bog',   s.paid_bog_total);
    setEl('cs-rev-lib',   s.paid_lib_total);
    setEl('cs-rev-cash',  s.paid_cash_total);
    setEl('cs-rev-extra', s.extra_income);
    setEl('cs-rev-total', s.total_revenue);
    setEl('bd-rev-tbc',   s.paid_tbc_total);
    setEl('bd-rev-bog',   s.paid_bog_total);
    setEl('bd-rev-lib',   s.paid_lib_total);
    setEl('bd-rev-cash',  s.paid_cash_total);
    setEl('bd-rev-extra', s.extra_income);

    // trend chart
    if (window.trendChart) {
        window.trendChart.data.labels                    = s.trend.map(t => t.month);
        window.trendChart.data.datasets[0].data          = s.trend.map(t => t.revenue);
        window.trendChart.data.datasets[1].data          = s.trend.map(t => t.cost);
        window.trendChart.data.datasets[2].data          = s.trend.map(t => t.profit);
        window.trendChart.update();
    }
}

// ══════════════════════════════════════════════════════════
// CHART.JS — TREND
// ══════════════════════════════════════════════════════════
const trendData = @json($stats['trend']);

window.trendChart = new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: trendData.map(t => t.month),
        datasets: [
            {
                label: 'შემოსავ.',
                data: trendData.map(t => t.revenue),
                backgroundColor: 'rgba(0,184,148,.7)',
                borderRadius: 5,
                order: 2,
            },
            {
                label: 'გასავ.',
                data: trendData.map(t => t.cost),
                backgroundColor: 'rgba(214,48,49,.6)',
                borderRadius: 5,
                order: 2,
            },
            {
                label: 'მოგება',
                data: trendData.map(t => t.profit),
                type: 'line',
                borderColor: '#0984e3',
                backgroundColor: 'rgba(9,132,227,.1)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#0984e3',
                fill: true,
                tension: 0.35,
                order: 1,
            },
        ],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index' },
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)} ₾`
                }
            }
        },
        scales: {
            y: { ticks: { callback: v => v.toFixed(0) + ' ₾' } }
        },
    }
});

// ══════════════════════════════════════════════════════════
// SAVE ENTRY
// ══════════════════════════════════════════════════════════
document.getElementById('form-entry')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn  = document.getElementById('btn-save-entry');
    btn.disabled = true;
    btn.textContent = 'შენახვა...';

    fetch('{{ route("finance.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: new FormData(this),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-entry'))?.hide();
            this.reset();
            // გვერდი განახლდება ახალ ჩანაწერს დასამატებლად
            window.location.reload();
        } else {
            alert(res.message || 'შეცდომა');
        }
    })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> შენახვა'; });
});

// ══════════════════════════════════════════════════════════
// SALARY CALCULATOR
// ══════════════════════════════════════════════════════════
let salaryData = null;

function loadSalary() {
    const month = document.getElementById('salary-month').value;
    if (!month) { alert('აირჩიეთ თვე'); return; }

    document.getElementById('salary-result').style.display  = 'none';
    document.getElementById('salary-loading').style.display = 'block';

    fetch(`{{ route('salary.calculate') }}?month=${month}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        salaryData = data;

        // ── Sale Operators ────────────────────────────────
        const saleBody = document.getElementById('salary-sale-body');
        saleBody.innerHTML = '';
        (data.sale_operators || []).forEach(op => {
            const recorded  = op.recorded !== null ? parseFloat(op.recorded) : null;
            const statusHtml = recorded !== null
                ? `<span class="badge-type badge-income">✔ ${recorded.toFixed(2)} ₾</span>`
                : `<span style="color:#b2bec3; font-size:11px;">—</span>`;

            saleBody.insertAdjacentHTML('beforeend', `
                <tr data-uid="${op.user_id}" data-role="sale_operator"
                    data-orders="${op.order_count}" data-deductions="${op.deduction_count}"
                    data-base="${op.base_amount}" data-bonus="${op.bonus_amount}"
                    data-deduction="${op.deduction_amount}">
                    <td style="font-weight:600;">${op.name}</td>
                    <td>${op.order_count} <small class="text-muted">(−${op.deduction_count})</small></td>
                    <td>${parseFloat(op.base_amount).toFixed(2)} ₾</td>
                    <td>${parseFloat(op.bonus_amount).toFixed(2)} ₾</td>
                    <td style="color:var(--red);">−${parseFloat(op.deduction_amount).toFixed(2)} ₾</td>
                    <td style="font-weight:700; color:var(--green);">
                        <input type="number" class="salary-amount-input" step="0.01" min="0"
                               value="${parseFloat(op.total_amount).toFixed(2)}"
                               style="width:90px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>
                        <input type="text" class="salary-note-input" placeholder="ჩანიშვნა..."
                               style="width:120px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>${statusHtml}</td>
                </tr>`);
        });

        // ── Warehouse Operators ───────────────────────────
        const whBody = document.getElementById('salary-wh-body');
        whBody.innerHTML = '';
        (data.warehouse_operators || []).forEach(op => {
            const recorded  = op.recorded !== null ? parseFloat(op.recorded) : null;
            const statusHtml = recorded !== null
                ? `<span class="badge-type badge-income">✔ ${recorded.toFixed(2)} ₾</span>`
                : `<span style="color:#b2bec3; font-size:11px;">—</span>`;

            whBody.insertAdjacentHTML('beforeend', `
                <tr data-uid="${op.user_id}" data-role="warehouse_operator"
                    data-orders="${op.order_count}">
                    <td style="font-weight:600;">${op.name}</td>
                    <td>
                        ${op.order_count}
                        ${(op.new_count !== undefined && (op.new_count > 0 || op.cancelled_count > 0))
                            ? `<small class="text-muted" style="font-size:10px; white-space:nowrap;">(${op.new_count} ახალი${op.cancelled_count > 0 ? ` −${op.cancelled_count} გაუქმ.` : ''})</small>`
                            : ''}
                    </td>
                    <td>${parseFloat(op.suggested_amount).toFixed(2)} ₾</td>
                    <td>
                        <input type="number" class="salary-amount-input" step="0.01" min="0"
                               value="${parseFloat(op.suggested_amount).toFixed(2)}"
                               style="width:90px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>
                        <input type="text" class="salary-note-input" placeholder="ჩანიშვნა..."
                               style="width:120px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>${statusHtml}</td>
                </tr>`);
        });

        // ── Admins ────────────────────────────────────────
        const adminBody = document.getElementById('salary-admin-body');
        adminBody.innerHTML = '';
        (data.admins || []).forEach(op => {
            const recorded  = op.recorded !== null ? parseFloat(op.recorded) : null;
            const statusHtml = recorded !== null
                ? `<span class="badge-type badge-income">✔ ${recorded.toFixed(2)} ₾</span>`
                : `<span style="color:#b2bec3; font-size:11px;">—</span>`;

            adminBody.insertAdjacentHTML('beforeend', `
                <tr data-uid="${op.user_id}" data-role="admin">
                    <td style="font-weight:600;">${op.name}</td>
                    <td>
                        <input type="number" class="salary-amount-input" step="0.01" min="0"
                               value="${recorded !== null ? recorded.toFixed(2) : '0.00'}"
                               style="width:90px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>
                        <input type="text" class="salary-note-input" placeholder="ჩანიშვნა..."
                               style="width:120px; border:1.5px solid #dfe6e9; border-radius:5px; padding:3px 6px; font-size:12px;">
                    </td>
                    <td>${statusHtml}</td>
                </tr>`);
        });

        document.getElementById('salary-loading').style.display = 'none';
        document.getElementById('salary-result').style.display  = 'block';
    })
    .catch(() => {
        document.getElementById('salary-loading').style.display = 'none';
        alert('შეცდომა მონაცემების ჩატვირთვისას');
    });
}

function recordSalaries() {
    const month = document.getElementById('salary-month').value;
    if (!month) { alert('თვე არ არის არჩეული'); return; }

    const payments = [];

    // collect all rows from all three tables
    document.querySelectorAll('#salary-sale-body tr, #salary-wh-body tr, #salary-admin-body tr').forEach(row => {
        const uid    = parseInt(row.dataset.uid);
        const role   = row.dataset.role;
        const amount = parseFloat(row.querySelector('.salary-amount-input')?.value || 0);
        const note   = row.querySelector('.salary-note-input')?.value || '';

        if (!uid || isNaN(amount)) return;

        const entry = {
            user_id:          uid,
            role:             role,
            total_amount:     amount,
            note:             note,
            order_count:      parseInt(row.dataset.orders     || 0),
            deduction_count:  parseInt(row.dataset.deductions || 0),
            base_amount:      parseFloat(row.dataset.base      || 0),
            bonus_amount:     parseFloat(row.dataset.bonus     || 0),
            deduction_amount: parseFloat(row.dataset.deduction || 0),
        };

        payments.push(entry);
    });

    if (!payments.length) { alert('ჩასაფიქსირებელი მონაცემები არ არის'); return; }

    const btn = document.getElementById('btn-record-salary');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ინახება...';

    fetch('{{ route("salary.record") }}', {
        method:  'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept':       'application/json',
        },
        body: JSON.stringify({ month, payments }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✔ ' + (res.message || 'ხელფასები ჩაფიქსირდა'));
            loadSalary(); // refresh to show recorded badges
        } else {
            alert(res.message || 'შეცდომა');
        }
    })
    .catch(() => alert('სერვერის შეცდომა'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save"></i> ხელფასების ჩაფიქსირება';
    });
}

// ══════════════════════════════════════════════════════════
// EXPANDABLE KPI CARDS
// ══════════════════════════════════════════════════════════
function toggleKpiCard(card) {
    card.classList.toggle('expanded');
}

// ══════════════════════════════════════════════════════════
// DELETE ENTRY
// ══════════════════════════════════════════════════════════
function deleteEntry(id) {
    if (!confirm('ჩანაწერი წაიშლება. დარწმუნებული ხართ?')) return;

    fetch(`/finance/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const row = document.getElementById('entry-row-' + id);
            if (row) row.remove();
            fetchStats();
        }
    });
}
</script>
@endsection

@endsection