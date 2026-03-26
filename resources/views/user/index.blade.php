@extends('layouts.master')

@section('top')
    <link rel="stylesheet" href="{{ asset('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@endsection

@section('content')

<div class="box box-success">
    <div class="box-header">
        <h3 class="box-title">List of System Users</h3>
    </div>

    @if(Auth::user()->role === 'admin')
    <div class="box-header">
        <a href="/register" class="btn btn-success"><i class="fa fa-plus"></i> Add User</a>
    </div>
    @endif

    <div class="box-body">
        <table id="user-table" class="table table-bordered table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Role Change Modal — მხოლოდ admin-ს უჩანს --}}
@if(Auth::user()->role === 'admin')
<div class="modal fade" id="role-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#f4f4f4;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-user-circle"></i> როლის შეცვლა</h4>
            </div>
            <div class="modal-body" style="padding:20px;">
                <p style="margin-bottom:15px; color:#666;">აირჩიე ახალი როლი:</p>
                <input type="hidden" id="role-user-id">
                <div style="display:flex; gap:10px;">
                    <button onclick="submitRole('admin')" class="btn btn-danger btn-block">
                        <i class="fa fa-shield"></i> ADMIN
                    </button>
                    <button onclick="submitRole('staff')" class="btn btn-primary btn-block">
                        <i class="fa fa-user"></i> STAFF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>




@endif
{{-- Edit User Modal --}}
@if(Auth::user()->role === 'admin')
<div class="modal fade" id="edit-user-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#f4f4f4;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-user-edit"></i> მომხმარებლის რედაქტირება</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_user_id">
                <div class="form-group">
                    <label>სახელი</label>
                    <input type="text" id="edit_user_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="edit_user_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>როლი</label>
                    <select id="edit_user_role" class="form-control">
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">გაუქმება</button>
                <button type="button" onclick="submitEdit()" class="btn btn-success">შენახვა</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('bot')
    <script src="{{ asset('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>

    <script>
        var table = $('#user-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('api.users') }}",
            columns: [
                {data: 'id',     name: 'id'},
                {data: 'name',   name: 'name'},
                {data: 'email',  name: 'email'},
                {data: 'role',   name: 'role',   orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        function changeRole(id, currentRole) {
            $('#role-user-id').val(id);
            $('#role-modal').modal('show');
        }

        function submitRole(newRole) {
            var id   = $('#role-user-id').val();
            var csrf = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url:  '/user/' + id + '/role',
                type: 'POST',
                data: { role: newRole, _token: csrf },
                success: function(data) {
                    $('#role-modal').modal('hide');
                    table.ajax.reload();
                    swal({ title: 'Success!', text: data.message, type: 'success', timer: 1500 });
                },
                error: function(xhr) {
                    $('#role-modal').modal('hide');
                    swal({ title: 'Oops...', text: xhr.responseJSON.message, type: 'error', timer: 2000 });
                }
            });
        }

        function deleteData(id) {
            var csrf = $('meta[name="csrf-token"]').attr('content');
            swal({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: '#d33',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(function() {
                $.ajax({
                    url:  "{{ url('user') }}" + '/' + id,
                    type: 'POST',
                    data: { _method: 'DELETE', _token: csrf },
                    success: function(data) {
                        table.ajax.reload();
                        swal({ title: 'Deleted!', text: data.message, type: 'success', timer: 1500 });
                    },
                    error: function(xhr) {
                        swal({ title: 'Oops...', text: xhr.responseJSON.message, type: 'error', timer: 2000 });
                    }
                });
            });
        }
        function editForm(id) {
    $.ajax({
        url: "{{ url('user') }}/" + id + "/edit",
        type: "GET",
        success: function(data) {
            $('#edit_user_id').val(data.id);
            $('#edit_user_name').val(data.name);
            $('#edit_user_email').val(data.email);
            $('#edit-user-modal').modal('show');
        },
        error: function() {
            swal("შეცდომა", "მონაცემები ვერ ჩაიტვირთა", "error");
        }
    });
}

function submitEdit() {
    var id   = $('#edit_user_id').val();
    var csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ url('user') }}/" + id,
        type: "POST",
        data: {
            _method: 'PATCH',
            _token:  csrf,
            name:    $('#edit_user_name').val().trim(),
            email:   $('#edit_user_email').val().trim()
        },
        success: function(data) {
            $('#edit-user-modal').modal('hide');
            table.ajax.reload();
            swal({ title: 'წარმატება!', text: data.message, type: 'success', timer: 1500 });
        },
        error: function(xhr) {
            swal("შეცდომა", xhr.responseJSON.message || "ვერ შეინახა", "error");
        }
    });
}
    </script>
    
    @if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        swal({
            title: 'წარმატება!',
            text: '{{ session('success') }}',
            type: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    });
    
</script>
@endif
@endsection