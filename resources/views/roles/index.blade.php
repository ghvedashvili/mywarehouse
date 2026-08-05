@extends('layouts.master')

@section('content')
@php
$roleStyles = [
    'sale_operator'      => ['icon' => 'fa-cart-shopping', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac', 'label_color' => '#15803d'],
    'warehouse_operator' => ['icon' => 'fa-warehouse',     'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#93c5fd', 'label_color' => '#1d4ed8'],
    'staff'              => ['icon' => 'fa-user-gear',     'color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#c4b5fd', 'label_color' => '#6d28d9'],
];
@endphp

<style>
.rp-wrap {
    max-width: 820px;
    margin: 0 auto;
    padding: 20px 16px 60px;
}

/* ── Header ── */
.rp-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}
.rp-header-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: #1e293b;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; color: #94a3b8;
    flex-shrink: 0;
}
.rp-header h5 { margin: 0; font-weight: 700; font-size: 16px; color: #1e293b; }
.rp-header p  { margin: 2px 0 0; font-size: 12px; color: #94a3b8; }

/* ── Role pills ── */
.rp-pills {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    margin-bottom: 20px;
    scrollbar-width: none;
}
.rp-pills::-webkit-scrollbar { display: none; }

.rp-pill {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    text-decoration: none;
}
.rp-pill .pill-icon {
    width: 26px; height: 26px;
    border-radius: 7px;
    background: #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    transition: all .15s;
}

@foreach($roles as $roleKey => $roleLabel)
@php $st = $roleStyles[$roleKey] ?? ['color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0','label_color'=>'#64748b']; @endphp
.rp-pill[data-role="{{ $roleKey }}"].active {
    background: {{ $st['bg'] }};
    border-color: {{ $st['border'] }};
    color: {{ $st['label_color'] }};
}
.rp-pill[data-role="{{ $roleKey }}"].active .pill-icon {
    background: {{ $st['color'] }};
    color: #fff;
}
@endforeach

/* ── Panes ── */
.rp-pane { display: none; }
.rp-pane.active { display: block; }

/* ── Table card ── */
.rp-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid #e9edf3;
    overflow: hidden;
    margin-bottom: 16px;
}

/* ── Table ── */
.rp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.rp-table thead tr {
    background: #f8fafc;
    border-bottom: 2px solid #e9edf3;
}
.rp-table thead th {
    padding: 11px 16px;
    font-weight: 700;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
}
.rp-table thead th:first-child { text-align: left; min-width: 140px; }
.rp-table thead th:not(:first-child) { text-align: center; width: 90px; }

.rp-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.rp-table tbody tr:last-child { border-bottom: none; }
.rp-table tbody tr:hover { background: #fafbfc; }

.rp-table tbody td {
    padding: 10px 16px;
    color: #1e293b;
    vertical-align: middle;
}
.rp-table tbody td:not(:first-child) { text-align: center; }

.page-cell {
    display: flex;
    align-items: center;
    gap: 9px;
}
.page-cell-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}
.page-cell-name { font-weight: 500; font-size: 13px; }

/* ── Toggle switch ── */
.perm-switch {
    position: relative;
    display: inline-block;
    width: 36px; height: 20px;
}
.perm-switch input { opacity: 0; width: 0; height: 0; }
.sw-track {
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: #e2e8f0;
    transition: background .2s;
    cursor: pointer;
}
.sw-track::before {
    content: '';
    position: absolute;
    left: 3px; top: 3px;
    width: 14px; height: 14px;
    border-radius: 50%;
    background: #fff;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.perm-switch input:checked + .sw-track { background: #16a34a; }
.perm-switch input:checked + .sw-track::before { transform: translateX(16px); }
.perm-switch input:disabled + .sw-track { opacity: .4; cursor: not-allowed; }

/* ── Save bar ── */
.rp-save-bar {
    display: flex;
    justify-content: flex-end;
    padding: 0 16px 14px;
}
.rp-save-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 26px;
    border-radius: 10px;
    background: #1e293b;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    border: none;
    cursor: pointer;
    transition: background .15s;
}
.rp-save-btn:hover { background: #334155; }
.rp-save-btn:disabled { opacity: .6; cursor: not-allowed; }

/* ── Mobile ── */
@media (max-width: 600px) {
    .rp-table { font-size: 12px; }
    .rp-table thead th { padding: 8px 10px; }
    .rp-table tbody td { padding: 7px 10px; }
    .rp-table thead th:not(:first-child) { width: 60px; }
    .page-cell-icon { width: 24px; height: 24px; font-size: 10px; border-radius: 6px; }
    .page-cell-name { font-size: 12px; }
    .page-cell { gap: 6px; }
    .perm-switch { width: 30px; height: 17px; }
    .sw-track::before { width: 11px; height: 11px; }
    .perm-switch input:checked + .sw-track::before { transform: translateX(13px); }
    .rp-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .rp-save-bar { padding: 0 10px 12px; }
    .rp-save-btn { padding: 9px 20px; font-size: 12px; }
}
</style>

<div class="rp-wrap">

    <div class="rp-header">
        <div class="rp-header-icon"><i class="fa fa-shield-halved"></i></div>
        <div>
            <h5>როლების უფლებები</h5>
            <p>კონფიგურირება — რა გვერდები ხელმისაწვდომია თითოეული როლისთვის</p>
        </div>
    </div>

    <div class="rp-pills">
        @foreach($roles as $roleKey => $roleLabel)
        @php $st = $roleStyles[$roleKey] ?? ['icon'=>'fa-user','color'=>'#64748b']; @endphp
        <a class="rp-pill {{ $loop->first ? 'active' : '' }}"
           data-role="{{ $roleKey }}"
           href="javascript:void(0)"
           onclick="switchRole('{{ $roleKey }}')">
            <span class="pill-icon"><i class="fa {{ $st['icon'] }}"></i></span>
            {{ $roleLabel }}
        </a>
        @endforeach
    </div>

    @foreach($roles as $roleKey => $roleLabel)
    @php $st = $roleStyles[$roleKey] ?? ['icon'=>'fa-user','color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0']; @endphp
    <div class="rp-pane {{ $loop->first ? 'active' : '' }}" id="pane-{{ $roleKey }}">
        <form class="permission-form" data-role="{{ $roleKey }}">
            @csrf
            <div class="rp-card">
                <div class="rp-table-wrap">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>გვერდი</th>
                                <th>ხედვა</th>
                                <th>რედ.</th>
                                <th>შექმნა</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $pageKey => $pageInfo)
                            @php $perm = $permissions[$roleKey][$pageKey] @endphp
                            <tr>
                                <td>
                                    <div class="page-cell">
                                        <div class="page-cell-icon"
                                             style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">
                                            <i class="fa {{ $pageInfo['icon'] }}"></i>
                                        </div>
                                        <span class="page-cell-name">{{ $pageInfo['label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <label class="perm-switch">
                                        <input class="perm-view"
                                               type="checkbox"
                                               name="permissions[{{ $pageKey }}][can_view]"
                                               value="1"
                                               data-page="{{ $pageKey }}"
                                               {{ $perm->can_view ? 'checked' : '' }}>
                                        <span class="sw-track"></span>
                                    </label>
                                </td>
                                <td>
                                    <label class="perm-switch">
                                        <input class="perm-write"
                                               type="checkbox"
                                               name="permissions[{{ $pageKey }}][can_edit]"
                                               value="1"
                                               data-page="{{ $pageKey }}"
                                               {{ $perm->can_edit ? 'checked' : '' }}
                                               {{ !$perm->can_view ? 'disabled' : '' }}>
                                        <span class="sw-track"></span>
                                    </label>
                                </td>
                                <td>
                                    <label class="perm-switch">
                                        <input class="perm-write"
                                               type="checkbox"
                                               name="permissions[{{ $pageKey }}][can_create]"
                                               value="1"
                                               data-page="{{ $pageKey }}"
                                               {{ $perm->can_create ? 'checked' : '' }}
                                               {{ !$perm->can_view ? 'disabled' : '' }}>
                                        <span class="sw-track"></span>
                                    </label>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="rp-save-bar">
                    <button type="submit" class="rp-save-btn">
                        <i class="fa fa-save rp-btn-icon"></i>
                        <span class="spinner-border spinner-border-sm rp-btn-spin" style="display:none;"></span>
                        <span class="rp-btn-text">შენახვა</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endforeach

</div>

<script>
function switchRole(roleKey) {
    document.querySelectorAll('.rp-pill').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.rp-pane').forEach(p => p.classList.remove('active'));
    document.querySelector('.rp-pill[data-role="'+roleKey+'"]').classList.add('active');
    document.getElementById('pane-'+roleKey).classList.add('active');
}

document.addEventListener('DOMContentLoaded', function () {

    $(document).on('change', '.perm-view', function () {
        var page = $(this).data('page');
        var $form = $(this).closest('form');
        var checked = this.checked;
        $form.find('.perm-write[data-page="'+page+'"]').each(function () {
            this.disabled = !checked;
            if (!checked) this.checked = false;
        });
    });

    $('.permission-form').each(function () {
        var $form = $(this);
        var $btn  = $form.find('.rp-save-btn');
        var $icon = $form.find('.rp-btn-icon');
        var $spin = $form.find('.rp-btn-spin');
        var $text = $form.find('.rp-btn-text');
        var role  = $form.data('role');

        $form.on('submit', function (e) {
            e.preventDefault();

            $btn.prop('disabled', true);
            $icon.hide();
            $spin.show();
            $text.text('შენახვა...');

            $.ajax({
                url:     '/roles/' + role,
                method:  'POST',
                data:    $form.serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'შენახულია', timer: 1400, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'შეცდომა', text: res.message || '' });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'შეცდომა' });
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    $icon.show();
                    $spin.hide();
                    $text.text('შენახვა');
                }
            });
        });
    });
});
</script>
@endsection
