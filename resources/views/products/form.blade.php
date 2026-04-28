<div class="modal fade" id="modal-form" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form id="form-item" method="post" enctype="multipart/form-data">
                @csrf @method('POST')

                {{-- HEADER --}}
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold mb-0"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body p-2 p-md-3">

                    <input type="hidden" id="id" name="id">

                    <div class="row g-2">

                        {{-- LEFT --}}
                        <div class="col-12 col-md-8">

                            {{-- NAME --}}
                            <div>
                                <label class="form-label small mb-1">Product Name</label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                            </div>

                            {{-- CATEGORY --}}
                            <div>
                                <label class="form-label small mb-1">Category</label>
                                <select name="category_id" id="category_id"
                                        class="form-select form-select-sm"
                                        required onchange="filterSizes()">
                                    <option value="">Choose...</option>
                                    @foreach($category as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- BRAND --}}
                            <div>
                                <label class="form-label small mb-1">Brand</label>
                                <select name="brand_id" id="brand_id" class="form-select form-select-sm" style="width:100%;">
                                    <option value="">-- Brand --</option>
                                    @foreach($brand as $b)
                                        <option value="{{ $b->id }}" data-logo="{{ $b->logo_url ?? '' }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SIZES --}}
                            <div>
                                <label class="form-label small text-muted mb-1">Sizes</label>
                                <div id="size-checkboxes"
                                     class="d-flex flex-wrap gap-1 border rounded p-2 bg-white"
                                     style="min-height:36px;">
                                    <span class="text-muted small">Select category</span>
                                </div>
                            </div>

                            {{-- CODE --}}
                            <div>
                                <label class="form-label small mb-1">Code</label>
                                <input type="text" class="form-control form-control-sm"
                                       id="product_code" name="product_code" required>
                            </div>

                            {{-- PRICE --}}
                            <div>
                                <label class="form-label small mb-1">Price</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₾</span>
                                    <input type="text" class="form-control fw-semibold text-success"
                                           id="price_geo" name="price_geo" required>
                                </div>
                            </div>

                            {{-- BUNDLE --}}
                            <div>
                                <label class="form-label small mb-1">კომპლექტი</label>
                                <select name="bundle_id" id="bundle_id" class="form-select form-select-sm">
                                    <option value="">— კომპლექტი არ არის —</option>
                                    @foreach($bundles as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SWITCHES --}}
                            <div class="mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           name="product_status" id="product_status" checked>
                                    <label class="form-check-label small" for="product_status">
                                        Active
                                    </label>
                                </div>
                            </div>

                        </div>

                        {{-- RIGHT IMAGE --}}
                        <div class="col-12 col-md-4">
                            <div class="text-center">

                                <label class="small fw-semibold d-block mb-1">Image</label>

                                <div id="image-preview"
                                     class="border rounded mb-2 d-flex align-items-center justify-content-center"
                                     style="height:140px; cursor:pointer;"
                                     onclick="document.getElementById('image').click()">

                                    <span class="text-muted small">Upload</span>
                                </div>

                                <input type="file" id="image" name="image"
                                       class="form-control form-control-sm d-none"
                                       accept="image/*">

                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm w-100"
                                        onclick="document.getElementById('image').click()">
                                    Choose
                                </button>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary"
                            data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" class="btn btn-sm btn-primary">
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('image').addEventListener('change', function() {
    let file = this.files[0];
    let preview = document.getElementById('image-preview');

    if (file && file.type.startsWith('image/')) {
        let reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML =
                `<img src="${e.target.result}" class="w-100 h-100" style="object-fit:cover;">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<span class="text-muted small">Upload</span>';
    }
});
</script>