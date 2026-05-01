@extends('layouts.master')
@section('page_title')<i class="fa fa-user-shield me-2" style="color:#c0392b;"></i>მომხმარებლები@endsection

@section('top')
<style>
:root { --u-red:#c0392b; --u-border:#dee2e6; --u-dark:#222d32; }
.wh-table thead th { background:#f4f4f4; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#555; border-bottom:2px solid var(--u-border)!important; white-space:nowrap; }
.mod-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 14px 8px; }
.mod-toolbar-search { position:relative; }
.mod-toolbar-search .search-icon { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#aaa; font-size:13px; pointer-events:none; }
.mod-toolbar-search input { padding-left:30px; width:220px; border-radius:6px; }
</style>
@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-user-shield me-2" style="color:#c0392b;"></i>სისტემის მომხმარებლები</h2>
            <p class="mod-subtitle">მომხმარებლებისა და როლების მართვა</p>
        </div>
        <div class="mod-actions">
            <button class="btn btn-secondary btn-sm" onclick="openChangePassword()">
                <i class="fa fa-key me-1"></i><span class="d-none d-sm-inline"> პაროლი</span>
            </button>
            @if(Auth::user()->role === 'admin')
            <button class="btn btn-success btn-sm" onclick="openCreateUser()">
                <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline"> დამატება</span>
            </button>
            @endif
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-toolbar">
            <select id="dt-page-length" class="form-select form-select-sm" style="width:75px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="-1">ყველა</option>
            </select>
            <div class="mod-toolbar-search">
                <i class="fa fa-search search-icon"></i>
                <input id="dt-search" type="search" class="form-control form-control-sm" placeholder="ძებნა...">
            </div>
        </div>
        <div class="table-responsive">
            <table id="user-table" class="table wh-table table-hover align-middle w-100">
                <thead>
                    <tr><th>ID</th><th>სახელი</th><th>Email</th><th>როლი</th><th>მოქმედება</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap --}}

{{-- ══ Create User Modal ══ --}}
@if(Auth::user()->role === 'admin')
<div class="modal fade" id="modal-create-user" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e,#c0392b);color:#fff;padding:14px 20px;">
                <h5 class="modal-title fw-bold"><i class="fa fa-user-plus me-2"></i>ახალი მომხმარებელი</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#f4f6fb;padding:20px;">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">სახელი</label>
                    <input type="text" id="cu-name" class="form-control" placeholder="სრული სახელი" style="border-radius:8px;">
                    <div class="invalid-feedback" id="cu-name-err"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">Email</label>
                    <input type="email" id="cu-email" class="form-control" placeholder="example@mail.com" style="border-radius:8px;">
                    <div class="invalid-feedback" id="cu-email-err"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">პაროლი</label>
                    <input type="password" id="cu-password" class="form-control" placeholder="მინ. 6 სიმბოლო" style="border-radius:8px;">
                    <div class="invalid-feedback" id="cu-password-err"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">პაროლის დადასტურება</label>
                    <input type="password" id="cu-password-confirm" class="form-control" placeholder="გაიმეორეთ" style="border-radius:8px;">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">როლი</label>
                    <select id="cu-role" class="form-select" style="border-radius:8px;">
                        <option value="staff">👤 Staff — ძირითადი წვდომა</option>
                        <option value="sale_operator">🛒 Sale Operator — გაყიდვები</option>
                        <option value="warehouse_operator">📦 Warehouse Operator — საწყობი</option>
                        <option value="admin">🛡 Admin — სრული წვდომა</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="background:#fff;">
                <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;border:1.5px solid #dee2e6;">გაუქმება</button>
                <button type="button" class="btn btn-success fw-semibold" onclick="submitCreateUser()" style="border-radius:8px;">
                    <i class="fa fa-check me-1"></i> შექმნა
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ══ Change Password Modal ══ --}}
<div class="modal fade" id="modal-change-password" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#222d32,#2c3e50);color:#fff;padding:14px 20px;">
                <h5 class="modal-title fw-bold"><i class="fa fa-key me-2"></i>პაროლის შეცვლა</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#f4f6fb;padding:20px;">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">მიმდინარე პაროლი</label>
                    <input type="password" id="cp-current" class="form-control" placeholder="მიმდინარე პაროლი" style="border-radius:8px;">
                    <div class="invalid-feedback" id="cp-current-err"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">ახალი პაროლი</label>
                    <input type="password" id="cp-new" class="form-control" placeholder="მინ. 6 სიმბოლო" style="border-radius:8px;">
                    <div class="invalid-feedback" id="cp-new-err"></div>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold" style="font-size:12px;text-transform:uppercase;color:#888;">დადასტურება</label>
                    <input type="password" id="cp-confirm" class="form-control" placeholder="გაიმეორეთ" style="border-radius:8px;">
                </div>
            </div>
            <div class="modal-footer" style="background:#fff;">
                <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;border:1.5px solid #dee2e6;">გაუქმება</button>
                <button type="button" class="btn btn-primary fw-semibold" onclick="submitChangePassword()" style="border-radius:8px;">
                    <i class="fa fa-save me-1"></i> შეცვლა
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Role Modal ══ --}}
@if(Auth::user()->role === 'admin')
<div class="modal fade" id="role-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:#c0392b;color:#fff;">
                <h5 class="modal-title fw-bold"><i class="fa fa-user-circle me-1"></i> როლის შეცვლა</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="role-user-id">
                <p class="fw-bold mb-1" id="role-current-name"></p>
                <p class="text-muted mb-3" style="font-size:12px;">აირჩიე ახალი როლი:</p>
                <div class="d-grid gap-2">
                    <button onclick="submitRole('admin')"              class="btn btn-danger btn-sm"><i class="fa fa-shield me-1"></i> Admin</button>
                    <button onclick="submitRole('staff')"              class="btn btn-primary btn-sm"><i class="fa fa-user me-1"></i> Staff</button>
                    <button onclick="submitRole('sale_operator')"      class="btn btn-success btn-sm"><i class="fa fa-shopping-cart me-1"></i> Sale Operator</button>
                    <button onclick="submitRole('warehouse_operator')" class="btn btn-warning btn-sm"><i class="fa fa-archive me-1"></i> Warehouse Operator</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ Edit User Modal ══ --}}
<div class="modal fade" id="edit-user-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:#2c3e50;color:#fff;">
                <h5 class="modal-title fw-bold"><i class="fa fa-edit me-1"></i> რედაქტირება</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_user_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">სახელი</label>
                    <input type="text" id="edit_user_name" class="form-control" style="border-radius:8px;">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold" style="font-size:12px;">Email</label>
                    <input type="email" id="edit_user_email" class="form-control" style="border-radius:8px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;">გაუქმება</button>
                <button type="button" onclick="submitEdit()" class="btn btn-success" style="border-radius:8px;"><i class="fa fa-check me-1"></i> შენახვა</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('bot')
<script>
var table = $('#user-table').DataTable({
    processing: true, serverSide: true,
    dom: 't<"d-flex justify-content-between align-items-center mt-2 px-2"ip>',
    ajax: "{{ route('api.users') }}",
    columns: [
        {data:'id', width:'50px'},
        {data:'name'},
        {data:'email'},
        {data:'role',    orderable:false},
        {data:'action',  orderable:false, searchable:false}
    ]
});

$('#dt-search').on('keyup',  function() { table.search(this.value).draw(); });
$('#dt-page-length').on('change', function() { table.page.len(this.value).draw(); });

// ── Create User ────────────────────────────────────────────────
function openCreateUser() {
    ['cu-name','cu-email','cu-password','cu-password-confirm'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.value = ''; el.classList.remove('is-invalid'); }
    });
    document.getElementById('cu-role').value = 'staff';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-create-user')).show();
}

function submitCreateUser() {
    var fields = ['cu-name','cu-email','cu-password','cu-password-confirm','cu-role'];
    fields.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('is-invalid');
    });
    $.ajax({
        url: "{{ route('user.store') }}",
        type: 'POST',
        data: {
            _token:                '{{ csrf_token() }}',
            name:                  $('#cu-name').val(),
            email:                 $('#cu-email').val(),
            password:              $('#cu-password').val(),
            password_confirmation: $('#cu-password-confirm').val(),
            role:                  $('#cu-role').val(),
        },
        success: function(res) {
            bootstrap.Modal.getInstance(document.getElementById('modal-create-user')).hide();
            table.ajax.reload();
            swal({ title: 'წარმატება!', text: res.message, icon: 'success', timer: 2000 });
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors || {};
            var map = { name:'cu-name', email:'cu-email', password:'cu-password' };
            Object.keys(map).forEach(function(field) {
                if (errors[field]) {
                    var el = document.getElementById(map[field]);
                    var errEl = document.getElementById(map[field]+'-err');
                    if (el) el.classList.add('is-invalid');
                    if (errEl) errEl.textContent = errors[field][0];
                }
            });
            if (!Object.keys(errors).length) {
                swal({ title: 'შეცდომა', text: xhr.responseJSON?.message || 'შეცდომა', icon: 'error' });
            }
        }
    });
}

// ── Change Password ────────────────────────────────────────────
function openChangePassword() {
    ['cp-current','cp-new','cp-confirm'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.value = ''; el.classList.remove('is-invalid'); }
    });
    var errEl = document.getElementById('cp-current-err');
    if (errEl) errEl.textContent = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-change-password')).show();
}

function submitChangePassword() {
    ['cp-current','cp-new'].forEach(function(id) {
        document.getElementById(id).classList.remove('is-invalid');
    });
    var newPass = $('#cp-new').val();
    var confirm = $('#cp-confirm').val();
    if (newPass !== confirm) {
        document.getElementById('cp-new').classList.add('is-invalid');
        document.getElementById('cp-new-err').textContent = 'პაროლები არ ემთხვევა.';
        return;
    }
    $.ajax({
        url: "{{ route('user.change-password') }}",
        type: 'POST',
        data: {
            _token:                '{{ csrf_token() }}',
            current_password:      $('#cp-current').val(),
            password:              newPass,
            password_confirmation: confirm,
        },
        success: function(res) {
            bootstrap.Modal.getInstance(document.getElementById('modal-change-password')).hide();
            swal({ title: 'წარმატება!', text: res.message, icon: 'success', timer: 2000 });
        },
        error: function(xhr) {
            var msg = xhr.responseJSON?.message || 'შეცდომა';
            document.getElementById('cp-current').classList.add('is-invalid');
            document.getElementById('cp-current-err').textContent = msg;
        }
    });
}

// ── Role ───────────────────────────────────────────────────────
function changeRole(id) {
    var row = table.rows().data().toArray().find(function(r){ return r.id == id; });
    $('#role-user-id').val(id);
    $('#role-current-name').text(row ? row.name : '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('role-modal')).show();
}

function submitRole(newRole) {
    $.ajax({
        url: '/user/' + $('#role-user-id').val() + '/role',
        type: 'POST',
        data: { role: newRole, _token: '{{ csrf_token() }}' },
        success: function(data) {
            bootstrap.Modal.getInstance(document.getElementById('role-modal')).hide();
            table.ajax.reload();
            swal({ title: 'წარმატება!', text: data.message, icon: 'success', timer: 1500 });
        },
        error: function(xhr) {
            swal({ title: 'შეცდომა', text: xhr.responseJSON?.message, icon: 'error' });
        }
    });
}

// ── Edit ───────────────────────────────────────────────────────
function editForm(id) {
    $.ajax({
        url: "{{ url('user') }}/" + id + "/edit", type: "GET",
        success: function(data) {
            $('#edit_user_id').val(data.id);
            $('#edit_user_name').val(data.name);
            $('#edit_user_email').val(data.email);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('edit-user-modal')).show();
        }
    });
}

function submitEdit() {
    $.ajax({
        url: "{{ url('user') }}/" + $('#edit_user_id').val(),
        type: "POST",
        data: { _method:'PATCH', _token:'{{ csrf_token() }}', name: $('#edit_user_name').val(), email: $('#edit_user_email').val() },
        success: function(data) {
            bootstrap.Modal.getInstance(document.getElementById('edit-user-modal')).hide();
            table.ajax.reload();
            swal({ title: 'წარმატება!', text: data.message, icon: 'success', timer: 1500 });
        },
        error: function(xhr) { swal({ title: 'შეცდომა', text: xhr.responseJSON?.message, icon: 'error' }); }
    });
}

// ── Delete ─────────────────────────────────────────────────────
function deleteData(id) {
    swal({ title: 'წაშლა?', text: 'ეს ოპერაცია შეუქცევადია', icon: 'warning', buttons: ['გაუქმება', 'წაშლა'], dangerMode: true })
    .then(function(confirmed) {
        if (confirmed) {
            $.ajax({
                url: "{{ url('user') }}/" + id, type: "POST",
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function() { table.ajax.reload(); swal({ title: 'წაიშალა!', icon: 'success', timer: 1500 }); },
                error: function(xhr) { swal({ title: 'შეცდომა', text: xhr.responseJSON?.message, icon: 'error' }); }
            });
        }
    });
}
</script>
@endsection
