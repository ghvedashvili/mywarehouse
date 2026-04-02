<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 10px;">
            <form id="form-item" method="post" class="form-horizontal" data-toggle="validator" enctype="multipart/form-data">
                {{ csrf_field() }} {{ method_field('POST') }}

                <div class="modal-header bg-gray-light">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="font-weight: bold;"></h4>
                </div>

                <div class="modal-body" style="padding: 20px 25px;">
                    <input type="hidden" id="id" name="id">

                    <div class="row">
                        <div class="col-md-8">

                            <div class="form-group mb-3">
                                <label style="font-weight: 600;">1. Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label style="font-weight: 600;">2. Category</label>
                                        <select name="category_id" id="category_id" class="form-control" required onchange="filterSizes()">
                                            <option value="">-- Choose Category --</option>
                                            @foreach($category as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Sizes — always visible, fixed height, no layout shift --}}
                                <div class="col-md-7">
                                    <label style="font-weight: 600; display: block;">Available Sizes</label>
                                    <div id="size-checkboxes" style="
                                        display: flex;
                                        flex-wrap: wrap;
                                        align-items: center;
                                        gap: 8px;
                                        height: 34px;          /* კატეგორიის select-ის სიმაღლე */
                                        overflow: hidden;
                                        padding: 4px 10px;
                                        background: #fdfdfd;
                                        border: 1px solid #ddd;
                                        border-radius: 4px;
                                        box-sizing: border-box;
                                    ">
                                        <span class="text-muted" id="sizes-placeholder" style="font-size: 12px; color: #aaa;">Choose a category first</span>
                                    </div>
                                    {{-- hidden group div — filterSizes() uses it, keep it here as stub --}}
                                    <div id="sizes-group" style="display:none;"></div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 5px;">
                                <label style="font-weight: 600;">3. Product Code</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" required>
                            </div>

                            <div class="well well-sm" style="background: #f4f4f4; border: 1px solid #ddd; padding: 15px; margin-top: 10px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label style="font-weight: 600;">Price GEO (₾)</label>
                                        <input type="text" class="form-control shadow-sm" id="price_geo" name="Price_geo" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 text-center" style="border-left: 1px solid #eee;">
                            <label style="font-weight: bold; display: block; margin-bottom: 15px;">პროდუქტის ფოტო</label>

                            <div id="image-preview" style="
                                width: 100%;
                                height: 180px;
                                border: 2px dashed #ddd;
                                border-radius: 10px;
                                margin-bottom: 15px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: #fff;
                                overflow: hidden;
                            ">
                                <span class="text-muted" id="preview-placeholder">No Preview</span>
                            </div>

                            <div class="form-group" style="padding: 0 10px;">
                                <input type="file" class="form-control input-sm" id="image" name="image"
                                       accept="image/*" style="cursor: pointer;">
                            </div>
                            <div style="padding: 0 10px; margin-top: 10px; display: flex; justify-content: space-around;">
    <div class="checkbox">
        <label style="font-weight: bold; color: #008d4c;">
            <input type="checkbox" name="product_status" id="product_status" value="1" checked> Active
        </label>
    </div>
    <div class="checkbox">
        <label style="font-weight: bold; color: #357ca5;">
            <input type="checkbox" name="in_warehouse" id="in_warehouse" value="1" checked> Warehouse
        </label>
    </div>
</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-gray-light" style="border-radius: 0 0 10px 10px;">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-flat" style="padding: 6px 40px; font-weight: bold;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- სურათის გადიდების მოდალი -->
<div class="modal fade" id="img-lightbox" tabindex="-1">
    <div class="modal-dialog modal-lg" style="margin-top: 80px;">
        <div class="modal-content" style="background:transparent; border:none; box-shadow:none;">
            <div class="modal-header" style="border:none; padding-bottom:0;">
                <button type="button" class="close" data-dismiss="modal">
                    <span style="color:#fff; font-size:36px;">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="img-lightbox-img" src="" class="img-responsive" style="margin:auto; max-height:75vh;">
            </div>
        </div>
    </div>
</div>
{{-- სურათის პრევიუ — ახალი ფაილის არჩევისას --}}
<script>
document.getElementById('image').addEventListener('change', function () {
    var file = this.files[0];
    var preview = document.getElementById('image-preview');

    if (file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML =
                '<img src="' + e.target.result + '" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<span class="text-muted" id="preview-placeholder">No Preview</span>';
    }
});


</script>