<div class="modal fade" id="modal-sale" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" class="form-horizontal" data-toggle="validator">
                {{ csrf_field() }} {{ method_field('POST') }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h3 class="modal-title">Add New Sale</h3>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" name="status_id" value="1"> <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="courier_servise_international" value="1">

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6"><label>Product</label>
                                <select name="product_id" id="product_id_sale" class="form-control" required>
                                    <option value="">-- Choice Product --</option>
                                    @foreach($all_products as $product)
                                        <option value="{{ $product->id }}" data-price-ge="{{ $product->price_geo }}" data-price-us="{{ $product->price_usa }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label>Customer</label>
                                <select name="customer_id" class="form-control" required>
                                    @foreach($customers as $id => $name) <option value="{{ $id }}">{{ $name }}</option> @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-6"><label>Price US ($)</label><input type="text" name="price_usa" id="price_usa_sale" class="form-control" readonly></div>
                            <div class="col-md-6"><label>Price GE (₾)</label><input type="text" name="price_georgia" id="price_georgia_sale" class="form-control" readonly></div>
                        </div>

                        <div class="well well-sm" style="margin-top:15px;">
                            <div class="row">
                                <div class="col-md-3"><label>TBC</label><input type="number" step="0.01" name="paid_tbc" class="form-control" value="0"></div>
                                <div class="col-md-3"><label>BOG</label><input type="number" step="0.01" name="paid_bog" class="form-control" value="0"></div>
                                <div class="col-md-3"><label>Lib</label><input type="number" step="0.01" name="paid_lib" class="form-control" value="0"></div>
                                <div class="col-md-3"><label>Cash</label><input type="number" step="0.01" name="paid_cash" class="form-control" value="0"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12"><input type="checkbox" name="courier_servise_local" value="1"> Local Courier</div>
                        </div>
                        <div class="form-group" style="padding:15px;"><label>Comment</label><textarea name="comment" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>