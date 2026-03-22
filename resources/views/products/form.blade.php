<div class="modal fade" id="modal-form" tabindex="1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-item" method="post" class="form-horizontal" data-toggle="validator" enctype="multipart/form-data">
                {{ csrf_field() }} {{ method_field('POST') }}

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title"></h3>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="id" name="id">

                    <div class="box-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" id="name" name="name" autofocus required>
                            <span class="help-block with-errors"></span>
                        </div>

                        <div class="form-group">
 
                            <label>Price_usa</label>
                            <input type="text" class="form-control" id="price_usa" name="Price_usa" required>
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group">
                            <label>Price_geo</label>
                            <input type="text" class="form-control" id="price_geo" name="Price_geo" required>
                            <span class="help-block with-errors"></span>
                        </div>

                        
<div class="form-group">
    <label>Product Code</label>
    <input type="text" class="form-control" id="product_code" name="product_code" required>
    <span class="help-block with-errors"></span>
</div>

<div class="form-group">
    <label>Category</label>
    <select name="category_id" id="category_id" class="form-control select" required onchange="filterSizes()">
        <option value="">-- Choose Category --</option>
        @foreach($category as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group" id="sizes-group" style="display:none;">
    <label>Available Sizes</label>
    <div id="size-checkboxes" style="display: flex; gap: 15px; flex-wrap: wrap; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
</div>

<div class="form-group">
    <div class="checkbox">
        <label><input type="checkbox" name="product_status" id="product_status" value="1" checked> Active (Visible in Shop)</label>
    </div>
    <div class="checkbox">
        <label><input type="checkbox" name="in_warehouse" id="in_warehouse" value="1" checked> In Warehouse</label>
    </div>
</div>
 
                            
   
                            <span class="help-block with-errors"></span>
                        </div>

                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <span class="help-block with-errors"></span>
                            <div id="image-preview" style="margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>