@extends('layouts.master')
@section('page_title')<i class="fa fa-user-shield me-2" style="color:#c0392b;"></i>მომხმარებლები@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-user-shield me-2" style="color:#c0392b;"></i>სისტემის მომხმარებლები</h2>
            <p class="mod-subtitle">მომხმარებლებისა და როლების მართვა</p>
        </div>
        <div class="mod-actions">
            @if(Auth::user()->role === 'admin')
            <a href="/register" class="btn btn-success btn-sm"><i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline"> დამატება</span></a>
            @endif
        </div>
    </div>

    <div class="mod-card">
        <div class="table-responsive">
            <table id="user-table" class="table table-hover align-middle w-100">
                <thead>
                    <tr><th>ID</th><th>სახელი</th><th>Email</th><th>როლი</th><th>მოქმედება</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>{{-- /mod-wrap --}}

@if(Auth::user()->role === 'admin')
<div class="modal fade" id="role-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:10px;">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-circle me-1"></i> როლის შეცვლა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="role-user-id">
                <p class="text-muted mb-2" id="role-current-name" style="font-weight:600;"></p>
                <p class="text-muted mb-3" style="font-size:12px;">აირჩიე ახალი როლი:</p>
                <div class="d-grid gap-2">
                    <button onclick="submitRole('admin')" class="btn btn-danger">
                        <i class="fa fa-shield me-1"></i> Admin — სრული წვდომა
                    </button>
                    <button onclick="submitRole('staff')" class="btn btn-primary">
                        <i class="fa fa-user me-1"></i> Staff — ძირითადი წვდომა
                    </button>
                    <button onclick="submitRole('sale_operator')" class="btn btn-success">
                        <i class="fa fa-shopping-cart me-1"></i> Sale Operator — გაყიდვები
                    </button>
                    <button onclick="submitRole('warehouse_operator')" class="btn btn-warning">
                        <i class="fa fa-archive me-1"></i> Warehouse Operator — საწყობი
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
    // find name from datatable row
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