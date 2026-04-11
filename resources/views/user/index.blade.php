@extends('layouts.master')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>System Users</span>
        @if(Auth::user()->role === 'admin')
        <a href="/register" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add User</a>
        @endif
    </div>
    <div class="card-body">
        <table id="user-table" class="table table-bordered table-hover table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@if(Auth::user()->role === 'admin')
<div class="modal fade" id="role-modal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:10px;">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-circle me-1"></i> როლის შეცვლა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="role-user-id">
                <p class="text-muted mb-3">აირჩიე ახალი როლი:</p>
                <div class="d-flex gap-2">
                    <button onclick="submitRole('admin')" class="btn btn-danger flex-fill">
                        <i class="fa fa-shield me-1"></i> ADMIN
                    </button>
                    <button onclick="submitRole('staff')" class="btn btn-primary flex-fill">
                        <i class="fa fa-user me-1"></i> STAFF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-user-modal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:10px;">
            <div class="modal-header">
                <h5 class="modal-title">მომხმარებლის რედაქტირება</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_user_id">
                <div class="mb-3">
                    <label class="form-label">სახელი</label>
                    <input type="text" id="edit_user_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" id="edit_user_email" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="button" onclick="submitEdit()" class="btn btn-success">შენახვა</button>
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
    ajax: "{{ route('api.users') }}",
    columns: [
        {data:'id'},{data:'name'},{data:'email'},
        {data:'role',orderable:false},
        {data:'action',orderable:false,searchable:false}
    ]
});

function changeRole(id) {
    $('#role-user-id').val(id);
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
            swal({ title: 'Success!', text: data.message, icon: 'success', timer: 1500 });
        },
        error: function(xhr) {
            swal({ title: 'Error', text: xhr.responseJSON.message, icon: 'error' });
        }
    });
}

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
        error: function(xhr) { swal({ title: 'შეცდომა', text: xhr.responseJSON.message, icon: 'error' }); }
    });
}

function deleteData(id) {
    swal({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
    .then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('user') }}/" + id, type: "POST",
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function(data) { table.ajax.reload(); swal({ title: 'Deleted!', icon: 'success', timer: 1500 }); },
                error: function(xhr) { swal({ title: 'Error', text: xhr.responseJSON.message, icon: 'error' }); }
            });
        }
    });
}
</script>

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    swal({ title: 'წარმატება!', text: '{{ session('success') }}', icon: 'success', timer: 2000 });
});
</script>
@endif
@endsection