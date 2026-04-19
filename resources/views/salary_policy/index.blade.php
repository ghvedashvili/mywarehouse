@extends('layouts.master')
@section('page_title')<i class="fa fa-sliders me-2" style="color:#8e44ad;"></i>სახელფასო პოლიტიკა@endsection

@section('content')
<div class="p-2 p-md-3">
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <span class="fw-bold" style="font-size:15px;">
            <i class="fa fa-sliders me-1" style="color:#8e44ad;"></i>სახელფასო პოლიტიკა
        </span>
        <div class="ms-auto">
            <button class="btn btn-success btn-sm" onclick="openForm()">
                <i class="fa fa-plus"></i> ახალი პოლიტიკა
            </button>
        </div>
    </div>

    <div class="card-body p-2 p-md-3">

        <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
            <i class="fa fa-info-circle me-1"></i>
            ახალი პოლიტიკის შექმნისას წინა პოლიტიკა ავტომატურად იხურება ახლის ამოქმედების თარიღით.
            ყოველთვის <strong>მხოლოდ ერთი</strong> პოლიტიკა იქნება აქტიური თითოეული როლისთვის.
        </div>

        @php
            $grouped   = $policies->groupBy('role');
            $roleOrder = ['sale_operator','warehouse_operator','staff','admin'];
            $today     = \Carbon\Carbon::today();
        @endphp

        @foreach($roleOrder as $role)
            @if($grouped->has($role))
            @php $rolePolicies = $grouped[$role]; @endphp
            <div class="mb-4">
                <h6 class="fw-bold mb-2" style="font-size:13px;color:#555;">
                    @if($role === 'sale_operator')       <i class="fa fa-user-tie me-1"    style="color:#2980b9;"></i> გამყიდველი (sale_operator)
                    @elseif($role === 'warehouse_operator') <i class="fa fa-warehouse me-1" style="color:#27ae60;"></i> საწყობი (warehouse_operator)
                    @elseif($role === 'staff')           <i class="fa fa-users me-1"       style="color:#e67e22;"></i> სტაფი (staff)
                    @elseif($role === 'admin')           <i class="fa fa-user-shield me-1" style="color:#8e44ad;"></i> ადმინი (admin)
                    @endif
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th>სახელი</th>
                                <th style="width:100px;">დაწყება</th>
                                <th style="width:100px;">დასრულება</th>
                                @if($role === 'sale_operator')
                                    <th style="width:110px;">₾ / ორდ</th>
                                    <th style="width:110px;">ბონუს %</th>
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
                                <td class="fw-semibold">{{ $p->name }}</td>
                                <td>{{ $p->effective_from->format('d.m.Y') }}</td>
                                <td class="text-muted" style="font-size:12px;">
                                    @if($isForever) <span class="text-muted">უვადო</span>
                                    @else {{ $p->effective_to->format('d.m.Y') }}
                                    @endif
                                </td>
                                @if($role === 'sale_operator')
                                    <td>{{ number_format($p->sale_base_per_order, 2) }} ₾</td>
                                    <td>{{ number_format($p->sale_bonus_percent * 100, 2) }} %</td>
                                @elseif($role === 'warehouse_operator')
                                    <td>{{ number_format($p->warehouse_per_order, 2) }} ₾</td>
                                @else
                                    <td>{{ number_format($p->fixed_salary ?? 0, 2) }} ₾</td>
                                @endif
                                <td>
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
                                <td>
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
            </div>
            @endif
        @endforeach

    </div>
</div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-policy" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold" id="modal-policy-title">ახალი პოლიტიკა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-policy">
                    <input type="hidden" id="policy_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">როლი</label>
                        <select id="f_role" class="form-select" onchange="onRoleChange()">
                            <option value="sale_operator">👤 გამყიდველი (sale_operator)</option>
                            <option value="warehouse_operator">🏭 საწყობი (warehouse_operator)</option>
                            <option value="staff">👥 სტაფი (staff)</option>
                            <option value="admin">🛡️ ადმინი (admin)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">სახელი</label>
                        <input type="text" id="f_name" class="form-control" placeholder="მაგ: 2026 Q3 პოლიტიკა" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">ამოქმედების თარიღი</label>
                            <input type="date" id="f_effective_from" class="form-control" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12 col-sm-6" id="wrap-effective-to" style="display:none;">
                            <label class="form-label fw-semibold">დასრულების თარიღი</label>
                            <input type="date" id="f_effective_to" class="form-control">
                        </div>
                    </div>
                    <div id="info-effective-to" class="form-text mb-3" style="display:none;"></div>
                    <div id="info-new-policy" class="alert alert-warning py-2 mb-3" style="font-size:12px;display:none;">
                        <i class="fa fa-triangle-exclamation me-1"></i>
                        ახალი პოლიტიკის შექმნისას წინა პოლიტიკა ავტომატურად დაიხურება ამ თარიღით.
                    </div>

                    {{-- sale_operator --}}
                    <div id="fields-sale" class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">₾ / ორდ <small class="text-muted">(საბაზო)</small></label>
                            <input type="number" id="f_sale_base" class="form-control" step="0.01" min="0">
                            <div class="form-text">მაგ: 3.00</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">ბონუს % <small class="text-muted">(sale_from)</small></label>
                            <input type="number" id="f_sale_bonus_display" class="form-control" step="0.01" min="0" max="100">
                            <div class="form-text">მაგ: 1 = 1%</div>
                        </div>
                    </div>

                    {{-- warehouse_operator --}}
                    <div id="fields-warehouse" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">₾ / ორდ</label>
                        <input type="number" id="f_warehouse" class="form-control" step="0.01" min="0">
                        <div class="form-text">მაგ: 1.00</div>
                    </div>

                    {{-- staff / admin --}}
                    <div id="fields-fixed" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">ფიქსირებული ხელფასი (₾)</label>
                        <input type="number" id="f_fixed_salary" class="form-control" step="0.01" min="0">
                        <div class="form-text">მაგ: 1500.00</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success" onclick="savePolicy()">
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
    var id   = $('#policy_id').val();
    var role = isEdit ? $('#f_role').val() : $('#f_role').val();

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

    var url    = id ? '/salary-policy/' + id : '/salary-policy';
    var method = id ? 'PATCH' : 'POST';
    if (method === 'PATCH') data['_method'] = 'PATCH';

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
        }
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
