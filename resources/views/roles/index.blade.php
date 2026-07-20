@extends('layouts.master')

@section('content')
<style>
@php
$roleStyles = [
    'sale_operator'      => ['icon' => 'fa-cart-shopping', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
    'warehouse_operator' => ['icon' => 'fa-warehouse',     'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
    'staff'              => ['icon' => 'fa-user-gear',     'color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
];
@endphp

.role-tabs { display: flex; gap: 8px; margin-bottom: 0; padding: 0; list-style: none; }
.role-tabs li { flex: 1; min-width: 0; }
.role-tab-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%;
    padding: 10px 12px; border-radius: 10px 10px 0 0;
    border: 1.5px solid #e2e8f0; border-bottom: none;
    background: #f8fafc; color: #64748b;
    font-weight: 600; font-size: clamp(10px, 1.2vw, 13.5px);
    cursor: pointer; transition: all .15s;
    text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.role-tab-btn .rt-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
    background: #e2e8f0; color: #64748b;
    transition: all .15s;
}
.role-tab-btn.active { background: #fff; color: #1e293b; border-color: #cbd5e1; }

@foreach($roles as $roleKey => $roleLabel)
@php $st = $roleStyles[$roleKey] ?? ['icon'=>'fa-user','color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0']; @endphp
.role-tab-btn[href="#tab-{{ $roleKey }}"].active .rt-icon,
.role-tab-btn[href="#tab-{{ $roleKey }}"]:hover .rt-icon {
    background: {{ $st['bg'] }}; color: {{ $st['color'] }}; border: 1px solid {{ $st['border'] }};
}
.role-tab-btn[href="#tab-{{ $roleKey }}"].active {
    border-top: 2.5px solid {{ $st['color'] }} !important;
}
@endforeach

.role-tab-btn:hover:not(.active) { background: #f1f5f9; color: #334155; }
.role-tab-pane { border: 1.5px solid #cbd5e1; border-radius: 0 10px 10px 10px; background: #fff; }
</style>

<div class="container-fluid px-4 py-4">

    <div class="d-flex align-items-center mb-4 gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center"
             style="width:44px;height:44px;background:#1a1f2e;">
            <i class="fa fa-shield-halved text-primary"></i>
        </div>
        <div>
            <h5 class="mb-0 fw-semibold">როლების უფლებები</h5>
            <small class="text-muted">კონფიგურირება რა გვერდები და მოქმედებები ხელმისაწვდომია თითოეული როლისთვის</small>
        </div>
    </div>

    {{-- Role tabs --}}
    <ul class="role-tabs" id="roleTabs" role="tablist">
        @foreach($roles as $roleKey => $roleLabel)
        @php $st = $roleStyles[$roleKey] ?? ['icon'=>'fa-user','color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0']; @endphp
        <li>
            <a class="role-tab-btn {{ $loop->first ? 'active' : '' }}"
               data-bs-toggle="tab"
               href="#tab-{{ $roleKey }}">
                <span class="rt-icon"><i class="fa {{ $st['icon'] }}"></i></span>
                {{ $roleLabel }}
            </a>
        </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($roles as $roleKey => $roleLabel)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} role-tab-pane" id="tab-{{ $roleKey }}">
            <div class="card border-0 rounded-0 rounded-bottom shadow-none">
                <div class="card-body p-0">
                    <form class="permission-form" data-role="{{ $roleKey }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-4" style="width:40%">გვერდი</th>
                                        <th class="text-center" style="width:20%">
                                            <i class="fa fa-eye me-1"></i> ხედვა
                                        </th>
                                        <th class="text-center" style="width:20%">
                                            <i class="fa fa-pen me-1"></i> რედ.
                                        </th>
                                        <th class="text-center" style="width:20%">
                                            <i class="fa fa-plus me-1"></i> შექმნა
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pages as $pageKey => $pageInfo)
                                    @php $perm = $permissions[$roleKey][$pageKey] @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <i class="fa {{ $pageInfo['icon'] }} me-2 text-secondary"></i>
                                            {{ $pageInfo['label'] }}
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view fs-5"
                                                       type="checkbox"
                                                       name="permissions[{{ $pageKey }}][can_view]"
                                                       value="1"
                                                       data-page="{{ $pageKey }}"
                                                       {{ $perm->can_view ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-write fs-5"
                                                       type="checkbox"
                                                       name="permissions[{{ $pageKey }}][can_edit]"
                                                       value="1"
                                                       data-page="{{ $pageKey }}"
                                                       {{ $perm->can_edit ? 'checked' : '' }}
                                                       {{ !$perm->can_view ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-write fs-5"
                                                       type="checkbox"
                                                       name="permissions[{{ $pageKey }}][can_create]"
                                                       value="1"
                                                       data-page="{{ $pageKey }}"
                                                       {{ $perm->can_create ? 'checked' : '' }}
                                                       {{ !$perm->can_view ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 border-top d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa fa-save me-1"></i> შენახვა
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // disable edit/create when view is unchecked
    document.querySelectorAll('.perm-view').forEach(function (viewCb) {
        viewCb.addEventListener('change', function () {
            const page = this.dataset.page;
            const form = this.closest('form');
            form.querySelectorAll('.perm-write[data-page="' + page + '"]').forEach(function (cb) {
                cb.disabled = !viewCb.checked;
                if (!viewCb.checked) cb.checked = false;
            });
        });
    });

    // AJAX save
    document.querySelectorAll('.permission-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const role = this.dataset.role;
            const btn  = this.querySelector('[type=submit]');
            const orig = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> შენახვა...';

            const data = new FormData(this);

            fetch('/roles/' + role, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: data,
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'შენახულია', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'შეცდომა' });
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'შეცდომა' });
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = orig;
            });
        });
    });

});
</script>
@endsection
