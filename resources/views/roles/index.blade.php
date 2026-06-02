@extends('layouts.master')

@section('content')
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
    <ul class="nav nav-tabs mb-0" id="roleTabs">
        @foreach($roles as $roleKey => $roleLabel)
        <li class="nav-item">
            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
               data-bs-toggle="tab"
               href="#tab-{{ $roleKey }}">
                <i class="fa fa-user-tag me-1 opacity-50"></i> {{ $roleLabel }}
            </a>
        </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($roles as $roleKey => $roleLabel)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $roleKey }}">
            <div class="card border-top-0 rounded-0 rounded-bottom">
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
                                            <i class="fa fa-pen me-1"></i> რედაქტირება
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
