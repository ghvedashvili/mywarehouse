<div class="modal fade" id="modal-sale" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">

            <form id="form-sale-content" method="post">
                {{ csrf_field() }} {{ method_field('POST') }}

                <!-- HEADER -->
                <div class="modal-header" style="background:#2c3e50; color:#fff;">
                    <h4 class="modal-title">🛒 Add New Sale</h4>
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;">×</button>
                </div>

                <div class="modal-body" style="background:#f5f7fa;">

                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" name="status_id" value="1">
                    <input type="hidden" name="courier_id" value="1">

                    <!-- TOP ROW -->
                    <div class="row">

                        <!-- LEFT SIDE -->
                        <div class="col-md-9">

                            <div style="background:#fff; padding:15px; border-radius:10px;">

                                <!-- ONE LINE -->
                                <div class="row align-items-end">

                                    <div class="col-md-3">
                                        <label>Product</label>
                                        <select name="product_id" id="product_id_sale" class="form-control">
                                            <option value="">-- Product --</option>
                                            @foreach($all_products as $product)
                                                <option value="{{ $product->id }}" 
                                                    data-price-ge="{{ $product->price_geo }}" 
                                                    data-price-us="{{ $product->price_usa }}"
                                                    data-sizes="{{ $product->sizes }}"
                                                    data-image="{{ asset(ltrim($product->image, '/')) }}">
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label>Size</label>
                                        <select name="size" id="size_sale" class="form-control">
                                            <option value="">-- Size --</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
    <label>₾ Price</label>
    <input type="text" name="price_georgia" id="price_georgia_sale" class="form-control text-center" readonly>
</div>

<div class="col-md-2">
    <label>$ Price</label>
    <input type="text" name="price_usa" id="price_usa_sale" class="form-control text-center" readonly>
</div>

                                    <div class="col-md-2">
                                        <label>Discount</label>
                                        <input type="number" name="discount" id="discount_sale" class="form-control" value="0">
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- RIGHT SIDE IMAGE -->
                        <div class="col-md-3">
                            <div style="background:#fff; padding:10px; border-radius:10px; text-align:center;">
                                <img id="target_image" src="" 
                                     style="width:100%; height:150px; object-fit:cover; border-radius:10px; display:none;">
                                <p id="no_image_text" class="text-muted">No image</p>
                            </div>
                        </div>

                    </div>

                    <!-- CUSTOMER -->
                    <div class="form-group" style="margin-top:15px;">
                        <label>Customer</label>
                        <select name="customer_id" class="form-control">
                            <option value="">-- Choose Customer --</option>
                            @foreach($customers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- PAYMENTS -->
                    <div style="background:#fff; padding:15px; border-radius:10px; margin-top:10px;">
                        <div class="row">
                            <div class="col-md-3"><input type="number" name="paid_tbc" class="form-control" placeholder="TBC"></div>
                            <div class="col-md-3"><input type="number" name="paid_bog" class="form-control" placeholder="BOG"></div>
                            <div class="col-md-3"><input type="number" name="paid_lib" class="form-control" placeholder="Lib"></div>
                            <div class="col-md-3"><input type="number" name="paid_cash" class="form-control" placeholder="Cash"></div>
                        </div>
                    </div>

                    <!-- COURIER -->
                    <div style="margin-top:10px;">
                        <label>
                            <input type="checkbox" id="is_local_courier"> 
                            🚚 Add Tbilisi Courier (+{{ $courier->tbilisi_price ?? 6 }} ₾)
                        </label>
                    </div>

                    <!-- STATUS -->
                    <div class="text-right" style="margin-top:10px;">
                        <strong>Status: <span id="sale_summary_text">Waiting...</span></strong>
                    </div>

                    <!-- COMMENT -->
                    <div class="form-group" style="margin-top:10px;">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Comment..."></textarea>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">💾 Save</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>

            </form>
        </div>
    </div>
</div>