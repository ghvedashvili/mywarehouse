@extends('layouts.master')
@section('page_title')<i class="fa fa-stethoscope me-2" style="color:#e17055;"></i>დიაგნოსტიკა@endsection

@section('content')
<div style="padding:20px; font-family:'Segoe UI',sans-serif;">

    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:20px;">
        <h4 style="margin:0 0 6px;color:#2d3436;font-weight:700;">
            <i class="fa fa-triangle-exclamation" style="color:#e17055;"></i>
            პრობლემური ორდერები — price_usa = 0
        </h4>
        <p style="margin:0 0 20px;color:#636e72;font-size:14px;">
            ახლის გარდა ყველა სტატუსის ორდერები, რომლებსაც თვითღირებულება არ აქვთ მინიჭებული.
        </p>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
            <button id="btnFind" onclick="findProblematic()"
                style="background:#0984e3;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:600;cursor:pointer;">
                <i class="fa fa-magnifying-glass"></i> პრობლემების ძიება
            </button>
            <button id="btnFix" onclick="fixAll()" disabled
                style="background:#00b894;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:600;cursor:pointer;opacity:0.5;">
                <i class="fa fa-wand-magic-sparkles"></i> ყველას ფასი გასწორება
            </button>
            <button id="btnFixSelected" onclick="fixSelected()" disabled
                style="background:#6c5ce7;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:600;cursor:pointer;opacity:0.5;">
                <i class="fa fa-check-double"></i> მონიშნულების გასწორება
            </button>
        </div>

        <div id="summary" style="display:none;margin-bottom:16px;"></div>
        <div id="loader" style="display:none;text-align:center;padding:30px;color:#636e72;">
            <i class="fa fa-spinner fa-spin fa-2x"></i><br>იტვირთება...
        </div>
        <div id="tableWrap" style="display:none;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">
                        <th style="padding:10px 8px;text-align:left;">
                            <input type="checkbox" id="checkAll" onchange="toggleAll(this)">
                        </th>
                        <th style="padding:10px 8px;text-align:left;">ორდერი</th>
                        <th style="padding:10px 8px;text-align:left;">პროდუქტი</th>
                        <th style="padding:10px 8px;text-align:center;">ზომა</th>
                        <th style="padding:10px 8px;text-align:center;">სტატუსი</th>
                        <th style="padding:10px 8px;text-align:left;">შესყიდვა #</th>
                        <th style="padding:10px 8px;text-align:right;">შეს. ღირ.</th>
                        <th style="padding:10px 8px;text-align:left;">მიზეზი</th>
                        <th style="padding:10px 8px;text-align:center;">შეიძლება გასწორება</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
        <div id="emptyMsg" style="display:none;text-align:center;padding:40px;color:#00b894;">
            <i class="fa fa-circle-check fa-2x"></i>
            <div style="margin-top:10px;font-size:16px;font-weight:600;">პრობლემური ორდერები არ მოიძებნა!</div>
        </div>
    </div>

</div>

<style>
#tableBody tr:hover { background:#f8f9fa; }
#tableBody tr td { padding:9px 8px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.badge-status { padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;color:#fff; }
.diag-can    { color:#27ae60;font-weight:600; }
.diag-cannot { color:#e17055; }
.diag-pill   { display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600; }
.diag-null   { background:#ffeaa7;color:#e17055; }
.diag-zero   { background:#fab1a0;color:#c0392b; }
.diag-noset  { background:#dfe6e9;color:#636e72; }
</style>

<script>
let allOrders = [];

document.addEventListener('DOMContentLoaded', findProblematic);

function buildSummary(total, canFix, totalEst) {
    const estHtml = canFix > 0
        ? ` &nbsp;|&nbsp; გასასწორებელი ჯამური თვითღ: <strong style="color:#0984e3;">$${totalEst.toFixed(2)}</strong>`
        : '';
    return `<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;color:#856404;">
        <strong><i class="fa fa-triangle-exclamation"></i> ნაპოვნია ${total} პრობლემური ორდერი.</strong>
        შეიძლება ავტომატურად გასწორდეს: <strong>${canFix}</strong>
        (დანარჩენებს შესყიდვა ან ფასი არ აქვს).${estHtml}
    </div>`;
}

function findProblematic() {
    document.getElementById('loader').style.display = 'block';
    document.getElementById('tableWrap').style.display = 'none';
    document.getElementById('emptyMsg').style.display = 'none';
    document.getElementById('summary').style.display = 'none';
    document.getElementById('btnFix').disabled = true;
    document.getElementById('btnFix').style.opacity = '0.5';
    document.getElementById('btnFixSelected').disabled = true;
    document.getElementById('btnFixSelected').style.opacity = '0.5';

    fetch(`{{ route("diagnostic.find") }}?_=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loader').style.display = 'none';
            allOrders = data.orders || [];

            if (allOrders.length === 0) {
                document.getElementById('emptyMsg').style.display = 'block';
                return;
            }

            const canFixCount = allOrders.filter(o => o.can_fix).length;
            const totalEst    = allOrders
                .filter(o => o.can_fix && o.estimated_price_usa !== null)
                .reduce((s, o) => s + parseFloat(o.estimated_price_usa), 0);

            document.getElementById('summary').innerHTML = buildSummary(data.count, canFixCount, totalEst);
            document.getElementById('summary').style.display = 'block';

            renderTable(allOrders);
            document.getElementById('tableWrap').style.display = 'block';

            if (canFixCount > 0) {
                document.getElementById('btnFix').disabled = false;
                document.getElementById('btnFix').style.opacity = '1';
            }
        })
        .catch(() => {
            document.getElementById('loader').style.display = 'none';
            alert('შეცდომა მოთხოვნისას!');
        });
}

function renderTable(orders) {
    const diagLabels = {
        'purchase_null':      ['შესყიდვა არ მიბმია', 'diag-null'],
        'purchase_zero_cost': ['შეს. ფასი = 0',       'diag-zero'],
        'price_not_set':      ['ფასი უბრალოდ დარჩა',  'diag-noset'],
    };

    const rows = orders.map(o => {
        const [diagText, diagClass] = diagLabels[o.diagnosis] || ['უცნობი', 'diag-noset'];
        const statusBadge = `<span class="badge-status label label-${o.status_color}">${o.status_name}</span>`;
        const canFixHtml = o.can_fix
            ? '<span class="diag-can"><i class="fa fa-check"></i> დიახ</span>'
            : '<span class="diag-cannot"><i class="fa fa-xmark"></i> არა</span>';
        const purchLink = o.purchase_order_id
            ? `<a href="/purchases?search=${o.purchase_order_id}" target="_blank" style="color:#0984e3;">#${o.purchase_order_id}</a>`
            : '<span style="color:#b2bec3;">—</span>';
        const purchCost = o.purchase_cost !== null && o.purchase_cost !== undefined
            ? `$${parseFloat(o.purchase_cost).toFixed(2)}`
            : '<span style="color:#b2bec3;">—</span>';

        return `<tr>
            <td><input type="checkbox" class="row-check" value="${o.id}" ${!o.can_fix ? 'disabled' : ''} onchange="onRowCheck()"></td>
            <td>
                <a href="/productsOut?search=${o.order_number}" target="_blank" style="color:#0984e3;font-weight:600;">${o.order_number}</a>
                <div style="font-size:11px;color:#b2bec3;">${o.created_at}</div>
            </td>
            <td>
                <div style="font-weight:600;">${o.product_name}</div>
                <div style="font-size:11px;color:#b2bec3;">${o.product_code}</div>
            </td>
            <td style="text-align:center;">${o.product_size || '—'}</td>
            <td style="text-align:center;">${statusBadge}</td>
            <td>${purchLink}</td>
            <td style="text-align:right;">${purchCost}</td>
            <td><span class="diag-pill ${diagClass}">${diagText}</span></td>
            <td style="text-align:center;">${canFixHtml}</td>
        </tr>`;
    });

    document.getElementById('tableBody').innerHTML = rows.join('');
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check:not(:disabled)').forEach(c => c.checked = cb.checked);
    onRowCheck();
}

function onRowCheck() {
    const anyChecked = [...document.querySelectorAll('.row-check:checked')].length > 0;
    document.getElementById('btnFixSelected').disabled = !anyChecked;
    document.getElementById('btnFixSelected').style.opacity = anyChecked ? '1' : '0.5';
}

function fixAll() {
    if (!confirm('ყველა გასასწორებელ ორდერს მიეწერება ფასი შესყიდვიდან. გავაგრძელოთ?')) return;
    doFix([]);
}

function fixSelected() {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => parseInt(c.value));
    if (ids.length === 0) return;
    if (!confirm(`${ids.length} ორდერს მიეწერება ფასი. გავაგრძელოთ?`)) return;
    doFix(ids);
}

function doFix(ids) {
    const btn1 = document.getElementById('btnFix');
    const btn2 = document.getElementById('btnFixSelected');
    btn1.disabled = true; btn1.style.opacity = '0.5';
    btn2.disabled = true; btn2.style.opacity = '0.5';
    btn1.innerHTML = '<i class="fa fa-spinner fa-spin"></i> მიმდინარეობს...';

    const sentIds = ids.length > 0 ? ids : allOrders.filter(o => o.can_fix).map(o => o.id);
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route("diagnostic.fix") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ ids }),
    })
        .then(r => r.json())
        .then(data => {
            btn1.innerHTML = '<i class="fa fa-wand-magic-sparkles"></i> ყველას ფასი გასწორება';

            const failedSet = new Set(data.failed || []);
            const fixedIds  = new Set(sentIds.filter(id => !failedSet.has(id)));

            // წავშალოთ გასწორებული სტრიქონები
            fixedIds.forEach(id => {
                const cb = document.querySelector(`.row-check[value="${id}"]`);
                if (cb) cb.closest('tr').remove();
            });

            // განვაახლოთ allOrders მასივი
            allOrders = allOrders.filter(o => !fixedIds.has(o.id));

            // განახლებული summary
            const canFixCount = allOrders.filter(o => o.can_fix).length;
            if (allOrders.length === 0) {
                document.getElementById('tableWrap').style.display = 'none';
                document.getElementById('summary').style.display = 'none';
                document.getElementById('emptyMsg').style.display = 'block';
                btn1.disabled = true; btn1.style.opacity = '0.5';
            } else {
                const totalEstAfter = allOrders
                    .filter(o => o.can_fix && o.estimated_price_usa !== null)
                    .reduce((s, o) => s + parseFloat(o.estimated_price_usa), 0);
                document.getElementById('summary').innerHTML = buildSummary(allOrders.length, canFixCount, totalEstAfter);
                if (canFixCount === 0) {
                    btn1.disabled = true; btn1.style.opacity = '0.5';
                } else {
                    btn1.disabled = false; btn1.style.opacity = '1';
                }
            }

            document.getElementById('checkAll').checked = false;
            btn2.disabled = true; btn2.style.opacity = '0.5';

            if (data.fixed > 0) {
                const msg = document.createElement('div');
                msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#00b894;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                msg.innerHTML = `<i class="fa fa-check-circle"></i> გასწორდა: ${data.fixed} ორდერი`;
                document.body.appendChild(msg);
                setTimeout(() => msg.remove(), 3000);
            }

            if (data.failed && data.failed.length > 0) {
                alert(`ვერ გასწორდა ${data.failed.length} ორდერი (შესყიდვაზე ფასი არ არის).`);
            }
        })
        .catch(() => {
            btn1.innerHTML = '<i class="fa fa-wand-magic-sparkles"></i> ყველას ფასი გასწორება';
            btn1.disabled = false; btn1.style.opacity = '1';
            alert('შეცდომა!');
        });
}
</script>
@endsection
