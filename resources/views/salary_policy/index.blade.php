@extends('layouts.master')
@section('page_title')<i class="fa fa-sliders me-2" style="color:#8e44ad;"></i>სახელფასო პოლიტიკა@endsection

@section('content')
@section('top')
<style>
@media (max-width: 767px) {
    .sp-section .table-responsive { overflow: visible !important; }
    .sp-section table { min-width: 0 !important; }
    .sp-section thead { display: none !important; }
    .sp-section table,
    .sp-section tbody { display: block !important; width: 100% !important; }
    .sp-section tr {
        display: grid !important;
        grid-template-columns: 1fr auto;
        background: #fff;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 10px 14px !important;
    }
    .sp-section tr:last-child { border-bottom: none !important; }
    .sp-section td { display: block !important; border: none !important; padding: 1px 0 !important; }

    .sp-td-name   { grid-column: 1; font-size: 14px; font-weight: 700; color: #1e293b; padding-bottom: 4px !important; }
    .sp-td-status { grid-column: 1; font-size: 12px; }
    .sp-td-from   { grid-column: 1; font-size: 11px; color: #64748b; }
    .sp-td-to     { display: none !important; }
    .sp-td-val    { grid-column: 1; font-size: 12px; color: #475569; }
    .sp-td-actions {
        grid-column: 2; grid-row: 1 / 4;
        display: flex !important; flex-direction: column;
        align-items: flex-end; justify-content: center;
        gap: 6px; padding-left: 10px !important;
    }
}
</style>
@endsection

@php
$spRoleStyles = [
    'sale_operator'      => ['icon'=>'fa-cart-shopping', 'label'=>'გამყიდველი',  'sub'=>'sale_operator',      'color'=>'#16a34a','bg'=>'#f0fdf4','border'=>'#bbf7d0'],
    'warehouse_operator' => ['icon'=>'fa-warehouse',     'label'=>'საწყობი',      'sub'=>'warehouse_operator', 'color'=>'#2563eb','bg'=>'#eff6ff','border'=>'#bfdbfe'],
    'staff'              => ['icon'=>'fa-user-gear',     'label'=>'სტაფი',        'sub'=>'staff',              'color'=>'#7c3aed','bg'=>'#f5f3ff','border'=>'#ddd6fe'],
    'admin'              => ['icon'=>'fa-user-shield',   'label'=>'ადმინი',       'sub'=>'admin',              'color'=>'#db2777','bg'=>'#fdf2f8','border'=>'#fbcfe8'],
];
@endphp
<style>
.sp-role-hdr {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 18px; border-bottom: 1px solid #f1f5f9;
    background: #fafbfd;
}
.sp-role-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.sp-role-name { font-size: 13px; font-weight: 700; color: #1e293b; }
.sp-role-sub  { font-size: 11px; color: #94a3b8; font-family: monospace; }
.sp-section   { border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 18px; }
.sp-section:last-child { margin-bottom: 0; }
</style>

<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-sliders me-2" style="color:#8e44ad;"></i>სახელფასო პოლიტიკა</h2>
            <p class="mod-subtitle">თანამშრომელთა სახელფასო წესების მართვა</p>
        </div>
        <div class="mod-actions">
            <button class="btn btn-success btn-sm" onclick="openForm()">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">ახალი პოლიტიკა</span>
            </button>
        </div>
    </div>

    <div class="mod-card">
    <div class="p-3 border-bottom" style="background:#fffbeb;">
        <div style="font-size:13px; color:#92400e;">
            <i class="fa fa-info-circle me-1"></i>
            ახალი პოლიტიკის შექმნისას წინა პოლიტიკა ავტომატურად იხურება ახლის ამოქმედების თარიღით.
            ყოველთვის <strong>მხოლოდ ერთი</strong> პოლიტიკა იქნება აქტიური თითოეული როლისთვის.
        </div>
    </div>
    <div class="p-3">

        @php
            $grouped   = $policies->groupBy('role');
            $roleOrder = ['sale_operator','warehouse_operator','staff','admin'];
            $today     = \Carbon\Carbon::today();
        @endphp

        @foreach($roleOrder as $role)
            @if($grouped->has($role))
            @php
                $rolePolicies = $grouped[$role];
                $rs = $spRoleStyles[$role] ?? ['icon'=>'fa-user','label'=>$role,'sub'=>$role,'color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0'];
            @endphp
            <div class="sp-section">
                <div class="sp-role-hdr">
                    <div class="sp-role-icon" style="background:{{ $rs['bg'] }};color:{{ $rs['color'] }};border:1px solid {{ $rs['border'] }};">
                        <i class="fa {{ $rs['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="sp-role-name">{{ $rs['label'] }}</div>
                        <div class="sp-role-sub">{{ $rs['sub'] }}</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-dark">
                            <tr>
                                <th>სახელი</th>
                                <th style="width:100px;">დაწყება</th>
                                <th style="width:100px;">დასრულება</th>
                                @if($role === 'sale_operator')
                                    <th style="width:110px;">₾ / ორდ</th>
                                    <th style="width:110px;" class="d-none d-md-table-cell">ბონუს %</th>
                                @elseif($role === 'warehouse_operator')
                                    <th style="width:110px;">₾ / ორდ</th>
                                @else
                                    <th style="width:130px;">ფიქსირებული (₾)</th>
                                @endif
                                <th style="width:170px;">სტატუსი</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rolePolicies as $p)
                            @php
                                $isForever = $p->effective_to->format('Y') === '2050';
                                if ($p->effective_from->gt($today)) {
                                    $statusType = 'pending';
                                } elseif ($p->effective_to->lte($today)) {
                                    $statusType = 'expired';
                                } else {
                                    $statusType = 'active';
                                }
                            @endphp
                            <tr>
                                <td class="sp-td-name fw-semibold">{{ $p->name }}</td>
                                <td class="sp-td-from">{{ $p->effective_from->format('d.m.Y') }}</td>
                                <td class="sp-td-to text-muted" style="font-size:12px;">
                                    @if($isForever) <span class="text-muted">უვადო</span>
                                    @else {{ $p->effective_to->format('d.m.Y') }}
                                    @endif
                                </td>
                                @if($role === 'sale_operator')
                                    <td class="sp-td-val">
                                        {{ number_format($p->sale_base_per_order, 2) }} ₾
                                        <span class="d-inline d-md-none text-muted"> / {{ number_format($p->sale_bonus_percent * 100, 2) }}%</span>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ number_format($p->sale_bonus_percent * 100, 2) }} %</td>
                                @elseif($role === 'warehouse_operator')
                                    <td class="sp-td-val">{{ number_format($p->warehouse_per_order, 2) }} ₾ / ორდ</td>
                                @else
                                    <td class="sp-td-val">{{ number_format($p->fixed_salary ?? 0, 2) }} ₾ ფიქსირებული</td>
                                @endif
                                <td class="sp-td-status">
                                    @if($statusType === 'active')
                                        <span class="badge bg-success">აქტიური</span>
                                        @if(!$isForever)
                                            <div class="text-muted" style="font-size:11px;">დასრულდება {{ $p->effective_to->format('d.m.Y') }}</div>
                                        @endif
                                    @elseif($statusType === 'pending')
                                        <span class="badge bg-warning text-dark">მოლოდინში</span>
                                        <div class="text-muted" style="font-size:11px;">ჩაირთვება {{ $p->effective_from->format('d.m.Y') }}</div>
                                    @else
                                        <span class="badge bg-secondary">ვადაგასული</span>
                                        <div class="text-muted" style="font-size:11px;">დასრულდა {{ $p->effective_to->format('d.m.Y') }}</div>
                                    @endif
                                </td>
                                <td class="sp-td-actions">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-primary btn-sm py-0 px-2"
                                                onclick="openEdit(
                                                    {{ $p->id }},
                                                    '{{ addslashes($p->name) }}',
                                                    '{{ $p->role }}',
                                                    {{ $p->sale_base_per_order ?? 'null' }},
                                                    {{ $p->sale_bonus_percent ?? 'null' }},
                                                    {{ $p->warehouse_per_order ?? 'null' }},
                                                    {{ $p->fixed_salary ?? 'null' }},
                                                    '{{ $p->effective_from->format('Y-m-d') }}',
                                                    '{{ $p->effective_to->format('Y-m-d') }}'
                                                )">
                                            <i class="fa fa-pen"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm py-0 px-2"
                                                onclick="deletePolicy({{ $p->id }})">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>{{-- /sp-section --}}
            @endif
        @endforeach

    </div>{{-- /p-3 --}}
    </div>{{-- /mod-card --}}

</div>{{-- /mod-wrap --}}

{{-- Modal --}}
<div class="modal fade" id="modal-policy" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header py-3 px-4" style="border-bottom:1px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:#f5f3ff;color:#7c3aed;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-sliders" style="font-size:13px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="modal-policy-title" style="font-size:15px;">ახალი პოლიტიკა</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="form-policy">
                    <input type="hidden" id="policy_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">როლი</label>
                        <select id="f_role" class="form-select" onchange="onRoleChange()">
                            <option value="sale_operator">გამყიდველი — sale_operator</option>
                            <option value="warehouse_operator">საწყობი — warehouse_operator</option>
                            <option value="staff">სტაფი — staff</option>
                            <option value="admin">ადმინი — admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">სახელი</label>
                        <input type="text" id="f_name" class="form-control" placeholder="მაგ: 2026 Q3 პოლიტიკა" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">ამოქმედების თარიღი</label>
                            <input type="date" id="f_effective_from" class="form-control" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12 col-sm-6" id="wrap-effective-to" style="display:none;">
                            <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">დასრულების თარიღი</label>
                            <input type="date" id="f_effective_to" class="form-control">
                        </div>
                    </div>
                    <div id="info-effective-to" class="form-text mb-3" style="display:none;"></div>

                    <div id="info-new-policy" class="mb-3 px-3 py-2 rounded-3" style="font-size:12px;display:none;background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
                        <i class="fa fa-triangle-exclamation me-1"></i>
                        ახალი პოლიტიკის შექმნისას წინა პოლიტიკა ავტომატურად დაიხურება ამ თარიღით.
                    </div>

                    {{-- sale_operator --}}
                    <div id="fields-sale" class="p-3 rounded-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">₾ / ორდ <span class="text-muted fw-normal">(საბაზო)</span></label>
                                <input type="number" id="f_sale_base" class="form-control" step="0.01" min="0" placeholder="3.00">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">ბონუს %</label>
                                <input type="number" id="f_sale_bonus_display" class="form-control" step="0.01" min="0" max="100" placeholder="1.00">
                                <div class="form-text">1 = 1%</div>
                            </div>
                        </div>
                    </div>

                    {{-- warehouse_operator --}}
                    <div id="fields-warehouse" class="p-3 rounded-3 mb-3" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;">
                        <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">₾ / ორდ</label>
                        <input type="number" id="f_warehouse" class="form-control" step="0.01" min="0" placeholder="1.00">
                    </div>

                    {{-- staff / admin --}}
                    <div id="fields-fixed" class="p-3 rounded-3 mb-3" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;">
                        <label class="form-label fw-semibold" style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">ფიქსირებული ხელფასი (₾)</label>
                        <input type="number" id="f_fixed_salary" class="form-control" step="0.01" min="0" placeholder="1500.00">
                    </div>
                </form>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top:1px solid #f1f5f9;background:#fafbfd;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" id="btn-save-policy" class="btn btn-success btn-sm" onclick="savePolicy()">
                    <i class="fa fa-save me-1"></i> შენახვა
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bot')
<script>
var modal   = new bootstrap.Modal(document.getElementById('modal-policy'));
var isEdit  = false;

function onRoleChange() {
    var role = $('#f_role').val();
    $('#fields-sale').toggle(role === 'sale_operator');
    $('#fields-warehouse').toggle(role === 'warehouse_operator');
    $('#fields-fixed').toggle(role === 'staff' || role === 'admin');
}

function openForm() {
    isEdit = false;
    $('#modal-policy-title').text('ახალი პოლიტიკა');
    $('#policy_id').val('');
    $('#f_role').val('sale_operator').prop('disabled', false);
    $('#f_name').val('');
    $('#f_effective_from').val(new Date().toISOString().slice(0,10));
    $('#f_sale_base').val(3.00);
    $('#f_sale_bonus_display').val(1.00);
    $('#f_warehouse').val(1.00);
    $('#f_fixed_salary').val('');
    $('#wrap-effective-to').hide();
    $('#info-effective-to').hide();
    $('#info-new-policy').show();
    onRoleChange();
    modal.show();
}

function openEdit(id, name, role, saleBase, saleBonus, warehouse, fixedSalary, effFrom, effTo) {
    isEdit = true;
    $('#modal-policy-title').text('პოლიტიკის რედაქტირება');
    $('#policy_id').val(id);
    $('#f_role').val(role).prop('disabled', true);
    $('#f_name').val(name);
    $('#f_effective_from').val(effFrom);
    $('#f_effective_to').val(effTo);
    $('#f_sale_base').val(saleBase !== null ? saleBase : '');
    $('#f_sale_bonus_display').val(saleBonus !== null ? (saleBonus * 100).toFixed(2) : '');
    $('#f_warehouse').val(warehouse !== null ? warehouse : '');
    $('#f_fixed_salary').val(fixedSalary !== null ? fixedSalary : '');
    var forever = effTo && effTo.startsWith('2050');
    $('#wrap-effective-to').show();
    $('#info-effective-to').text(forever ? 'დასრულების თარიღი: უვადო (2050)' : '').toggle(!forever);
    $('#info-new-policy').hide();
    onRoleChange();
    modal.show();
}

function savePolicy() {
    var $btn = $('#btn-save-policy');
    if ($btn.prop('disabled')) return;
    $btn.prop('disabled', true).css('opacity', '0.65');

    var id   = $('#policy_id').val();
    var data = {
        _token:         '{{ csrf_token() }}',
        role:           $('#f_role').val(),
        name:           $('#f_name').val(),
        effective_from: $('#f_effective_from').val(),
    };

    if (isEdit) {
        data.effective_to = $('#f_effective_to').val() || '2050-01-01';
    }

    if (data.role === 'sale_operator') {
        data.sale_base_per_order = $('#f_sale_base').val();
        data.sale_bonus_percent  = parseFloat($('#f_sale_bonus_display').val()) / 100;
    } else if (data.role === 'warehouse_operator') {
        data.warehouse_per_order = $('#f_warehouse').val();
    } else {
        data.fixed_salary = $('#f_fixed_salary').val();
    }

    var url = id ? '/salary-policy/' + id : '/salary-policy';
    if (id) data['_method'] = 'PATCH';

    $.ajax({
        url: url, type: 'POST', data: data,
        success: function(res) {
            modal.hide();
            Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                .then(function() { location.reload(); });
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა';
            Swal.fire({ icon: 'error', title: 'შეცდომა', text: msg });
        },
        complete: function() { $btn.prop('disabled', false).css('opacity', ''); }
    });
}

function deletePolicy(id) {
    Swal.fire({
        icon: 'warning', title: 'წაიშალოს?',
        showCancelButton: true,
        confirmButtonText: 'წაშლა', cancelButtonText: 'გაუქმება',
        confirmButtonColor: '#dc3545'
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: '/salary-policy/' + id, type: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
            success: function(res) {
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                    .then(function() { location.reload(); });
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა';
                Swal.fire({ icon: 'error', title: 'შეცდომა', text: msg });
            }
        });
    });
}
</script>
@endsection
