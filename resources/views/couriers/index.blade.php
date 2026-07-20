@extends('layouts.master')
@section('page_title')<i class="fa fa-truck me-2" style="color:#0984e3;"></i>კურიერები@endsection

@section('content')
<style>
.cr-wrap { padding: 24px; font-family: 'Segoe UI', sans-serif; max-width: 900px; }

.cr-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    overflow: hidden;
}

.cr-card-header {
    padding: 16px 22px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cr-card-header .title {
    font-size: 15px;
    font-weight: 700;
    color: #2d3436;
}
.cr-card-header .subtitle {
    font-size: 12px;
    color: #b2bec3;
    margin-top: 2px;
}

.cr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cr-table th {
    background: #f8f9fa;
    padding: 10px 18px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #b2bec3;
    letter-spacing: .4px;
    border-bottom: 1px solid #f0f0f0;
}
.cr-table th.num { text-align: right; }
.cr-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f8f9f8;
    vertical-align: middle;
}
.cr-table tr:last-child td { border-bottom: none; }
.cr-table tr:hover td { background: #fafbfc; }

.cr-name { font-weight: 700; color: #2d3436; font-size: 14px; }
.cr-price {
    text-align: right;
    font-weight: 700;
    font-size: 14px;
    font-family: 'Courier New', monospace;
}
.cr-price .currency { font-size: 11px; color: #b2bec3; font-weight: 400; margin-left: 2px; }

.badge-intl { background: #eff6ff; color: #0984e3; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; }
.badge-tbs  { background: #f0fff8; color: #00b894; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; }
.badge-reg  { background: #fff8f0; color: #e17055; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; }
.badge-vil  { background: #f5f3ff; color: #6c5ce7; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; }

.btn-cr-edit {
    background: #fff;
    border: 1.5px solid #0984e3;
    color: #0984e3;
    border-radius: 8px;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
}
.btn-cr-edit:hover { background: #0984e3; color: #fff; }

/* modal */
.cr-field label { font-size: 12px; font-weight: 700; color: #636e72; margin-bottom: 4px; display: block; }
.cr-field input  { border: 1.5px solid #dfe6e9; border-radius: 8px; padding: 8px 12px; font-size: 13px; width: 100%; }
.cr-field input:focus { border-color: #0984e3; outline: none; box-shadow: 0 0 0 3px rgba(9,132,227,.12); }

.price-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
.price-card { background: #f8f9fa; border-radius: 10px; padding: 12px 14px; }
.price-card .pc-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #b2bec3; margin-bottom: 6px; }
.price-card input { background: #fff; }

@media (max-width: 767px) {
    .cr-wrap { padding: 10px; max-width: 100%; }
    .cr-table thead { display: none; }
    .cr-table, .cr-table tbody { display: block; }
    .cr-table tr {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0;
        background: #fff; border-radius: 12px; margin: 0 0 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,.07); border: 1px solid #e9edf3;
        overflow: hidden; padding: 12px 14px 10px;
    }
    .cr-table td { display: block; border: none !important; padding: 0; }
    .cr-td-id   { display: none !important; }
    .cr-td-name { width: 100%; margin-bottom: 10px; }
    .cr-td-name .cr-name { font-size: 15px; }
    .cr-td-tbs, .cr-td-reg, .cr-td-vil { flex: 1; text-align: center !important; }
    .cr-td-tbs::before { content: 'თბ'; display: block; font-size: 10px; font-weight: 700; color: #00b894; margin-bottom: 2px; }
    .cr-td-reg::before { content: 'რეგ'; display: block; font-size: 10px; font-weight: 700; color: #e17055; margin-bottom: 2px; }
    .cr-td-vil::before { content: 'სოფ'; display: block; font-size: 10px; font-weight: 700; color: #6c5ce7; margin-bottom: 2px; }
    .cr-price { font-size: 13px !important; }
    .cr-td-act { width: 100%; text-align: right !important; margin-top: 10px; padding-top: 10px !important; border-top: 1px solid #f0f0f0 !important; }
}
</style>

<div class="cr-wrap">
    <div class="cr-card">
        <div class="cr-card-header">
            <div>
                <div class="title"><i class="fa fa-truck me-2" style="color:#0984e3;"></i>კურიერის სერვისები</div>
                <div class="subtitle">მხოლოდ რედაქტირება — ახლის დამატება და წაშლა არ არის ხელმისაწვდომი</div>
            </div>
        </div>

        <table class="cr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>სახელი</th>
                    <th class="num"><span class="badge-tbs">თბილისი</span></th>
                    <th class="num"><span class="badge-reg">რეგიონი</span></th>
                    <th class="num"><span class="badge-vil">სოფელი</span></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($couriers as $courier)
                <tr>
                    <td class="cr-td-id" style="color:#b2bec3; font-size:12px; width:32px;">{{ $courier->id }}</td>
                    <td class="cr-td-name"><span class="cr-name">{{ $courier->name }}</span></td>
                    <td class="cr-td-tbs cr-price" style="color:#00b894;">
                        {{ number_format($courier->tbilisi_price, 2) }}<span class="currency">₾</span>
                    </td>
                    <td class="cr-td-reg cr-price" style="color:#e17055;">
                        {{ number_format($courier->region_price, 2) }}<span class="currency">₾</span>
                    </td>
                    <td class="cr-td-vil cr-price" style="color:#6c5ce7;">
                        {{ number_format($courier->village_price, 2) }}<span class="currency">₾</span>
                    </td>
                    <td class="cr-td-act" style="text-align:right; width:110px;">
                        <button class="btn-cr-edit" onclick="editCourier({{ $courier->id }}, {{ json_encode($courier) }})">
                            <i class="fa fa-pen me-1"></i> რედაქტ.
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="modal-courier" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background:#f8f9fa; border-bottom:1px solid #f0f0f0;">
                <h5 class="modal-title" style="font-size:15px; font-weight:700;">
                    <i class="fa fa-pen me-2" style="color:#0984e3;"></i>კურიერის რედაქტირება
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <input type="hidden" id="cr-edit-id">

                <div class="cr-field">
                    <label>სახელი</label>
                    <input type="text" id="cr-name" placeholder="კურიერის სახელი">
                </div>

                <div class="price-grid">
                    <div class="price-card" style="border-left:3px solid #00b894;">
                        <div class="pc-label">თბილისი (₾)</div>
                        <div class="cr-field" style="margin:0;">
                            <input type="number" id="cr-tbs" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="price-card" style="border-left:3px solid #e17055;">
                        <div class="pc-label">რეგიონი (₾)</div>
                        <div class="cr-field" style="margin:0;">
                            <input type="number" id="cr-reg" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="price-card" style="border-left:3px solid #6c5ce7;">
                        <div class="pc-label">სოფელი (₾)</div>
                        <div class="cr-field" style="margin:0;">
                            <input type="number" id="cr-vil" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background:#f8f9fa; border-top:1px solid #f0f0f0; gap:8px;">
                <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btn-cr-save" onclick="saveCourier()">
                    <i class="fa fa-check me-1"></i> შენახვა
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script>
function editCourier(id, data) {
    document.getElementById('cr-edit-id').value = id;
    document.getElementById('cr-name').value = data.name;
    document.getElementById('cr-tbs').value  = data.tbilisi_price;
    document.getElementById('cr-reg').value  = data.region_price;
    document.getElementById('cr-vil').value  = data.village_price;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-courier')).show();
}

function saveCourier() {
    var $btn = $('#btn-cr-save');
    if ($btn.prop('disabled')) return;
    $btn.prop('disabled', true).css('opacity', '0.65');

    var id = document.getElementById('cr-edit-id').value;

    $.ajax({
        url: '/couriers/' + id,
        type: 'POST',
        data: {
            _method:              'PATCH',
            _token:               '{{ csrf_token() }}',
            name:                 $('#cr-name').val(),
            tbilisi_price:        $('#cr-tbs').val(),
            region_price:         $('#cr-reg').val(),
            village_price:        $('#cr-vil').val(),
        },
        success: function(res) {
            bootstrap.Modal.getInstance(document.getElementById('modal-courier')).hide();
            Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false })
                .then(function() { location.reload(); });
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'შეცდომა';
            Swal.fire({ icon: 'error', title: 'შეცდომა', text: msg });
        },
        complete: function() { $btn.prop('disabled', false).css('opacity', ''); }
    });
}
</script>
@endsection
