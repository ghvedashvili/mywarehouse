@extends('layouts.master')
@section('page_title')<i class="fa fa-cubes me-2" style="color:#0ea5e9;"></i>კომპლექტები@endsection

@section('content')
<div class="mod-wrap">

    <div class="mod-header">
        <div>
            <h2 class="mod-title"><i class="fa fa-cubes me-2" style="color:#0ea5e9;"></i>კომპლექტები</h2>
            <p class="mod-subtitle">პროდუქტების კომპლექტების მართვა</p>
        </div>
        <div class="mod-actions">
            <button onclick="openCreateModal()" class="btn btn-success btn-sm">
                <i class="fa fa-plus me-1"></i> ახალი კომპლექტი
            </button>
        </div>
    </div>

    <div class="mod-card">
        <div class="table-responsive">
            <table id="bundles-table" class="table table-sm table-hover mb-0" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>სახელი</th>
                        <th>პროდუქტები</th>
                        <th style="width:90px"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="modal-bundle" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0" id="bundle-modal-title">ახალი კომპლექტი</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-bundle" method="post">
                @csrf
                <input type="hidden" id="bundle_id_field" name="id">
                <div class="modal-body">
                    <label class="form-label small mb-1">სახელი</label>
                    <input type="text" class="form-control form-control-sm" id="bundle_name" name="name"
                           required placeholder="მაგ: შორტი + მაისური კომპლექტი">
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-sm btn-primary">შენახვა</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('bot')
<script>
var bundleTable;

function openCreateModal() {
    $('#bundle-modal-title').text('ახალი კომპლექტი');
    $('#bundle_id_field').val('');
    $('#bundle_name').val('');
    new bootstrap.Modal(document.getElementById('modal-bundle')).show();
}

$(function() {
    bundleTable = $('#bundles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("api.productBundles") }}',
        columns: [
            { data: 'id',             name: 'id',             width: '50px' },
            { data: 'name',           name: 'name' },
            { data: 'products_list', name: 'products_list', searchable: false, orderable: false },
            { data: 'action',         name: 'action', orderable: false, searchable: false, className: 'text-end' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ka.json' },
        dom: 'rt<"d-flex justify-content-between align-items-center mt-2"ip>',
        pageLength: 25,
    });

    $(document).on('click', '.btn-edit', function() {
        $('#bundle-modal-title').text('კომპლექტის რედაქტირება');
        $('#bundle_id_field').val($(this).data('id'));
        $('#bundle_name').val($(this).data('name'));
        new bootstrap.Modal(document.getElementById('modal-bundle')).show();
    });

    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        swal({ title: 'წაშლა?', icon: 'warning', buttons: ['გაუქმება', 'წაშლა'], dangerMode: true })
            .then(function(ok) {
                if (!ok) return;
                $.ajax({
                    url: '/product-bundles/' + id,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function() { bundleTable.ajax.reload(); },
                    error: function(xhr) { swal('შეცდომა', xhr.responseJSON?.message || '', 'error'); }
                });
            });
    });

    $('#form-bundle').on('submit', function(e) {
        e.preventDefault();
        var id   = $('#bundle_id_field').val();
        var url  = id ? '/product-bundles/' + id : '/product-bundles';
        var data = { _token: '{{ csrf_token() }}', name: $('#bundle_name').val() };
        if (id) data._method = 'PATCH';

        $.ajax({
            url: url, type: 'POST', data: data,
            success: function() {
                bootstrap.Modal.getInstance(document.getElementById('modal-bundle')).hide();
                bundleTable.ajax.reload();
            },
            error: function(xhr) {
                swal('შეცდომა', xhr.responseJSON?.errors?.name?.[0] || xhr.responseJSON?.message || '', 'error');
            }
        });
    });
});
</script>
@endsection
