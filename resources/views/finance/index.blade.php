@extends('layouts.master')

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

<div class="fin-wrap">

    <div class="fin-title">💰 ფინანსების <span>დაშბორდი</span></div>

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
            <div class="kpi-sub">status=4 ორდერი</div>
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
        <div class="kpi-card" style="--accent: var(--blue);">
            <div class="kpi-label">💵 შემოსავალი (სულ)</div>
            <div class="kpi-value" id="kpi-total-rev">{{ number_format($stats['total_revenue'],2) }} ₾</div>
            <div class="kpi-sub">გაყიდვები + დამატებითი</div>
        </div>
        <div class="kpi-card" style="--accent: var(--red);">
            <div class="kpi-label">📤 გასავალი (სულ)</div>
            <div class="kpi-value" id="kpi-total-cost">{{ number_format($stats['total_cost'],2) }} ₾</div>
            <div class="kpi-sub">თვითღირებულება + ხარჯები</div>
        </div>
        <div class="kpi-card {{ $stats['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}" id="kpi-profit-card">
            <div class="kpi-label">{{ $stats['profit'] >= 0 ? '📈 მოგება' : '📉 წაგება' }}</div>
            <div class="kpi-value" id="kpi-profit">{{ number_format($stats['profit'],2) }} ₾</div>
            <div class="kpi-sub" id="kpi-margin">მარჟა: {{ $stats['profit_margin'] }}%</div>
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

        {{-- Cost Breakdown --}}
        <div class="col-breakdown">
            <div class="fin-card">
                <div class="fin-card-title">🧮 გასავლის დაშლა</div>

                <div id="breakdownList">
                    @php
                        $catLabels = \App\Models\FinanceEntry::$categoryLabels;
                        $totalCost = $stats['total_cost'];
                    @endphp

                    {{-- product cost --}}
                    @php $pct = $totalCost > 0 ? round($stats['sale_cost_price']/$totalCost*100) : 0; @endphp
                    <div class="breakdown-item">
                        <div class="bd-top">
                            <span class="bd-label">🏷️ პროდ. თვითღირ.</span>
                            <span class="bd-val">{{ number_format($stats['sale_cost_price'],2) }} ₾</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%; background: var(--orange);"></div></div>
                    </div>

                    {{-- courier --}}
                    @php $pct = $totalCost > 0 ? round($stats['sale_courier']/$totalCost*100) : 0; @endphp
                    <div class="breakdown-item">
                        <div class="bd-top">
                            <span class="bd-label">🚚 საკურიერო</span>
                            <span class="bd-val">{{ number_format($stats['sale_courier'],2) }} ₾</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-bar-fill" style="width:{{ $pct }}%; background: var(--purple);"></div></div>
                    </div>

                    {{-- extra expenses by category --}}
                    @foreach($stats['expense_by_category'] as $cat => $amount)
                        @php $pct = $totalCost > 0 ? round($amount/$totalCost*100) : 0; @endphp
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
                        <span class="lbl">პროდ. თვითღირ.</span>
                        <span class="val" id="cs-cost">{{ number_format($stats['sale_cost_price'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">საკურიერო</span>
                        <span class="val" id="cs-courier">{{ number_format($stats['sale_courier'],2) }} ₾</span>
                    </div>
                    <div class="cost-row">
                        <span class="lbl">დამ. ხარჯები</span>
                        <span class="val" id="cs-extra">{{ number_format($stats['extra_expense'],2) }} ₾</span>
                    </div>
                    <div class="cost-row total-row">
                        <span class="lbl">სულ გასავალი</span>
                        <span class="val" id="cs-total">{{ number_format($stats['total_cost'],2) }} ₾</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ ENTRIES TABLE ══ --}}
    <div class="entries-section">
        <div class="entries-header">
            <div class="title">📋 დამატებითი ჩანაწერები</div>
            @if(auth()->user()->role === 'admin')
            <button class="btn-add-entry" data-toggle="modal" data-target="#modal-entry">
                + ჩანაწერის დამატება
            </button>
            @endif
        </div>

        <table class="entries-table">
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
    </div>

</div>{{-- /fin-wrap --}}

{{-- ══ MODAL: ADD ENTRY ══ --}}
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="modal-entry" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:10px;">
            <form id="form-entry">
                @csrf
                <div class="modal-header fin-modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                    <button type="button" class="btn btn-default" data-dismiss="modal">გაუქმება</button>
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

function updateUI(s) {
    document.getElementById('kpi-sale-count').textContent   = s.sale_count;
    document.getElementById('kpi-return-count').textContent  = s.return_count;
    document.getElementById('kpi-return-amount').textContent = '-' + fmt(s.return_amount);
    document.getElementById('kpi-change-count').textContent  = s.change_count;
    document.getElementById('kpi-total-rev').textContent     = fmt(s.total_revenue);
    document.getElementById('kpi-total-cost').textContent = fmt(s.total_cost);
    document.getElementById('kpi-profit').textContent     = fmt(s.profit);
    document.getElementById('kpi-margin').textContent     = 'მარჟა: ' + s.profit_margin + '%';

    const profitCard = document.getElementById('kpi-profit-card');
    profitCard.classList.toggle('profit-positive', s.profit >= 0);
    profitCard.classList.toggle('profit-negative', s.profit < 0);
    profitCard.querySelector('.kpi-label').textContent = s.profit >= 0 ? '📈 მოგება' : '📉 წაგება';

    document.getElementById('cs-cost').textContent    = fmt(s.sale_cost_price);
    document.getElementById('cs-courier').textContent = fmt(s.sale_courier);
    document.getElementById('cs-extra').textContent   = fmt(s.extra_expense);
    document.getElementById('cs-total').textContent   = fmt(s.total_cost);

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
            $('#modal-entry').modal('hide');
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