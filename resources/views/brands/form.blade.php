<div class="modal fade" id="modal-form" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:10px;">
            <form id="form-item" method="post" data-toggle="validator" enctype="multipart/form-data">
                @csrf @method('POST')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">

                    <div class="row g-3">
                        <div class="col-8">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">სახელი</label>
                                <input type="text" class="form-control" id="name" name="name" autofocus required>
                            </div>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">ლოგო</label>
                            <div id="brand-logo-preview"
                                 class="border rounded d-flex align-items-center justify-content-center"
                                 style="height:72px;cursor:pointer;background:#f8fafc;"
                                 onclick="document.getElementById('logo').click()">
                                <span class="text-muted small" id="logo-placeholder"><i class="fa fa-image"></i></span>
                            </div>
                            <input type="file" id="logo" name="logo"
                                   class="d-none" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('logo').addEventListener('change', function() {
    var file = this.files[0];
    var preview = document.getElementById('brand-logo-preview');
    if (file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-height:68px;max-width:100%;object-fit:contain;border-radius:4px;">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<span class="text-muted small"><i class="fa fa-image"></i></span>';
    }
});
</script>
