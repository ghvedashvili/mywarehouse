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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="name" name="name" autofocus required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sizes <small class="text-muted">(მაგ: S, M, L ან 37, 38)</small></label>
                        <input type="text" class="form-control" id="sizes" name="sizes" placeholder="მძიმით გამოყოფილი">
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