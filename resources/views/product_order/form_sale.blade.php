@php
    $isAdmin = auth()->user()->role === 'admin';
@endphp

<div class="modal fade" id="modal-sale" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="form-sale-content" method="post">
                {{ csrf_field() }} {{ method_field('POST') }}

                <!-- HEADER -->
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">🛒 Add Sale</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body bg-light">

                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" name="status_id" value="1">
                    <input type="hidden" name="courier_id" value="1">

                    <div class="row">

                        <!-- LEFT -->
                        <div class="col-md-9">

                            <div class="card mb-3">
                                <div class="card-body">

                                    <div class="row align-items-end">

                                        <!-- PRODUCT -->
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

                                        <!-- SIZE -->
                                        <div class="col-md-2">
                                            <label>Size</label>
                                            <select name="product_size" id="size_sale" class="form-control">
                                                <option value="">-- Size --</option>
                                            </select>
                                        </div>

                                        <!-- PRICE GE -->
                                        <div class="col-md-2">
                                            <label>₾ Price</label>
                                            <p id="price_georgia_text" class="form-control text-center bg-white mb-0">0</p>
                                            <input type="hidden" name="price_georgia" id="price_georgia_sale">
                                        </div>

                                        <!-- PRICE USA (ADMIN ONLY) -->
                                        @if($isAdmin)
                                        <div class="col-md-2">
                                            <label>$ Price</label>
                                            <p id="price_usa_text" class="form-control text-center bg-white mb-0">0</p>
                                            <input type="hidden" name="price_usa" id="price_usa_sale">
                                        </div>
                                        @endif

                                        <!-- DISCOUNT -->
                                        @if($isAdmin)
                                        <div class="col-md-2">
                                            <label>Discount</label>
                                            <input type="number" name="discount" id="discount_sale" class="form-control" value="0">
                                        </div>
                                        @endif

                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- IMAGE -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body p-2">
                                    <img id="target_image" class="img-fluid rounded" style="max-height:150px; display:none;">
                                    <small id="no_image_text" class="text-muted">No image</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- CUSTOMER -->
                  <div class="form-group">
    <label>Customer</label>
    <select name="customer_id" id="customer_id_sale" class="form-control" style="width:100%">
        <option value="">-- Choose Customer --</option>
        @foreach($customers as $customer)
            <option value="{{ $customer->id }}" 
                data-address="{{ $customer->address }}" 
                data-city="{{ $customer->city->name ?? '' }}" 
                data-tel="{{ $customer->tel }}" 
                data-alt="{{ $customer->alternative_tel }}" 
                data-comment="{{ $customer->comment }}">
                {{ $customer->name }} ({{ $customer->tel }})
            </option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm btn-primary mt-1" onclick="openCustomerCreate()">Add New Customer</button>
</div>

<!-- Display fields automatically -->
<div id="customer_info_fields" style="display:none;">
    <p>City + Address: <span id="customer_address"></span></p>
    <p>Tel: <span id="customer_tel"></span></p>
    <p>Alternative Tel: <span id="customer_alt_tel"></span></p>
    <p>Comment: <span id="customer_comment"></span></p>
</div>
                    <!-- PAYMENTS (ADMIN ONLY) -->
                    @if($isAdmin)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3"><input type="number" name="paid_tbc" class="form-control" placeholder="TBC"></div>
                                <div class="col-md-3"><input type="number" name="paid_bog" class="form-control" placeholder="BOG"></div>
                                <div class="col-md-3"><input type="number" name="paid_lib" class="form-control" placeholder="Lib"></div>
                                <div class="col-md-3"><input type="number" name="paid_cash" class="form-control" placeholder="Cash"></div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- COURIER -->
                    <div class="mt-2">
                        <label>
                            <input type="checkbox" id="is_local_courier" name="courier_servise_local" value="1">
        🚚 Tbilisi Courier (+{{ $courier->tbilisi_price ?? 6 }} ₾)
                        </label>
                    </div>

                    <!-- STATUS -->
                    <div class="text-right mt-2">
                        <strong>Status: <span id="sale_summary_text">Waiting...</span></strong>
                    </div>

                    <!-- COMMENT -->
                    <div class="form-group mt-2">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Comment..."></textarea>
                    </div>

                </div>
@if(auth()->user()->role === 'admin')
<div class="form-group" id="status_field_wrapper">
    <label>სტატუსი</label>
    <select name="status_id" id="status_id_sale" class="form-control">
        @foreach($statuses as $status)
            <option value="{{ $status->id }}">{{ $status->name }}</option>
        @endforeach
    </select>
</div>
@endif
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">💾 Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>

            </form>
        </div>
    </div>
</div>




<!-- Status Quick-Change Modal -->
<div class="modal fade" id="modal-status" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" style="margin-top: 150px;" role="document">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: #2c3e50; border-radius: 8px 8px 0 0; padding: 12px 16px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:0.8; font-size:18px;">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title" style="color:#fff; font-size:14px; font-weight:600;">
                    <i class="fa fa-tag"></i> სტატუსის შეცვლა
                </h4>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <input type="hidden" id="status_order_id">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-size:12px; color:#666; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                        აირჩიე სტატუსი
                    </label>
                    <select id="quick_status_select" class="form-control" style="border-radius:6px; height:38px; font-size:13px;">
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" data-color="{{ $status->color }}">
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 12px 16px; border-radius: 0 0 8px 8px;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success btn-sm" onclick="saveQuickStatus()" style="min-width:80px;">
                    <i class="fa fa-check"></i> შენახვა
                </button>
            </div>
        </div>
    </div>
</div>
@include('customers.form')