@php
    $isAdmin = auth()->user()->role === 'admin';
@endphp

<div class="modal fade" id="modal-sale" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 10px;">
            <form id="form-sale-content" method="post" class="form-horizontal" enctype="multipart/form-data">
                {{ csrf_field() }} {{ method_field('POST') }}

                <input type="hidden" name="id" id="id">
                <input type="hidden" name="order_type" value="sale">
                {{-- დამალული input-ები JS-ისთვის --}}
                <input type="hidden" name="price_georgia" id="price_georgia_sale">
                <input type="hidden" name="price_usa" id="price_usa_sale">
                <input type="hidden" id="courier_price_tbilisi" name="courier_price_tbilisi" value="0">
                <input type="hidden" id="db_tbilisi_price" value="{{ $courier->tbilisi_price ?? 6 }}">

                <div class="modal-header bg-gray-light">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="font-weight: bold;">🛒 Add New Sale</h4>
                </div>

                <div class="modal-body" style="padding: 20px 25px;">
                    <div class="row">
                        <div class="col-md-8">
                            
                            {{-- 1. PRODUCT, SIZE & PRICES ROW --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label style="font-weight: 600;">1. Product</label>
                                        <select name="product_id" id="product_id_sale" class="form-control select2" style="width: 100%;" required>
                                            <option value="">— Choose Product —</option>
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
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="font-weight: 600;">Size</label>
                                        <select name="product_size" id="size_sale" class="form-control" required>
                                            <option value="">Size</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <label style="font-weight: 600;">Price (₾)</label>
                                    <div id="price_georgia_text" class="form-control" style="background:#f9f9f9; font-weight:bold; color:#00a65a; font-size:14px; padding: 6px 2px;">0</div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <label style="font-weight: 600;">Price ($)</label>
                                    <div id="price_usa_text" class="form-control" style="background:#f9f9f9; font-weight:bold; color:#357ca5; font-size:14px; padding: 6px 2px;">0</div>
                                </div>
                            </div>

                            {{-- 2. CUSTOMER --}}
                            <div class="form-group" style="margin-top: 10px;">
                                <label style="font-weight: 600;">2. Customer 
                                    <button type="button" class="btn btn-link btn-xs pull-right" data-toggle="modal" data-target="#modal-form">+ Add New</button>
                                </label>
                                <select name="customer_id" id="customer_id_sale" class="form-control select2" style="width: 100%;" required>
                                    <option value="">— Choose Customer —</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            data-address="{{ $customer->address }}"
                                            data-city-id="{{ $customer->city_id }}" 
                                            data-city="{{ $customer->city->name ?? '' }}"
                                            data-tel="{{ $customer->tel }}"
                                            data-alt="{{ $customer->alternative_tel }}"
                                            data-comment="{{ $customer->comment }}">
                                            {{ $customer->name }} ({{ $customer->tel }})
                                        </option>
                                    @endforeach
                                </select>

                                <div id="customer_info_fields" style="display:none; margin-top: 10px; background: #fdfdfd; border: 1px solid #eee; padding: 8px; border-radius: 5px;">
                                    <div style="font-size: 12px; line-height: 1.6;">
                                        <div>📍 <span id="customer_address" style="font-weight:600;"></span></div>
                                        <div style="display:inline-block; margin-right:15px;">📞 <span id="customer_tel"></span></div>
                                        <div style="display:inline-block;">📱 <span id="customer_alt_tel"></span></div>
                                        <div style="color: #777; border-top: 1px solid #eee; margin-top: 5px; padding-top: 3px;">
                                            📝 <span id="customer_comment"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. FINANCE --}}
                            @if($isAdmin)
                            <div class="well well-sm" style="background: #f4f4f4; border: 1px solid #ddd; padding: 10px; margin-top: 15px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="small" style="font-weight:600;">Discount</label>
                                        <input type="number" name="discount" id="discount_sale" class="form-control input-sm" value="0">
                                    </div>
                                    
                                </div>
                                <div class="input-group input-group-sm" style="margin-top:10px;">
                                    <span class="input-group-addon">TBC</span>
                                    <input type="number" name="paid_tbc" class="form-control" placeholder="0" step="0.01">
                                    <span class="input-group-addon">BOG</span>
                                    <input type="number" name="paid_bog" class="form-control" placeholder="0" step="0.01">
                                    <span class="input-group-addon">Lib</span>
                                    <input type="number" name="paid_lib" class="form-control" placeholder="0" step="0.01">
                                    <span class="input-group-addon">Cash</span>
                                    <input type="number" name="paid_cash" class="form-control" placeholder="0" step="0.01">
                                </div>
                                <div style="margin: 10px 0; min-height: 40px;">
                                    <small style="display:block; color:#777;">Summary:</small>
                                    <strong id="sale_summary_text" style="font-size: 13px;">შეიყვანეთ მონაცემები</strong>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- RIGHT SIDE: IMAGE & COURIER --}}
                        <div class="col-md-4 text-center" style="border-left: 1px solid #eee;">
                            <label style="font-weight: bold; display: block; margin-bottom: 10px;">Preview</label>
                            <div style="width: 100%; height: 160px; border: 2px dashed #ddd; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #fff; overflow: hidden; margin-bottom: 15px;">
                                <img id="target_image" class="img-responsive" style="display:none; max-height: 155px;">
                                <span id="no_image_text" class="text-muted italic">No Image</span>
                            </div>

                            <div class="well well-sm text-left" style="background:#fff; border:1px solid #eee; padding:10px;">
                                <!-- ახალი -->
<div style="margin-top:0;">
    <label style="font-weight: 600; display:block; margin-bottom:6px; font-size:13px;">🚚 Courier</label>
    
    <label style="display:block; font-size:13px; margin-bottom:4px; cursor:pointer;">
        <input type="radio" name="courier_type" id="courier_tbilisi" value="tbilisi"> 
        Tbilisi (+{{ $courier->tbilisi_price ?? 6 }} ₾)
    </label>
    <label style="display:block; font-size:13px; margin-bottom:4px; cursor:pointer;">
        <input type="radio" name="courier_type" id="courier_region" value="region"> 
        Region (+{{ $courier->region_price ?? 9 }} ₾)
    </label>
    <label style="display:block; font-size:13px; margin-bottom:4px; cursor:pointer;">
        <input type="radio" name="courier_type" id="courier_village" value="village"> 
        Village (+{{ $courier->village_price ?? 13 }} ₾)
    </label>
    <label style="display:block; font-size:13px; color:#999; cursor:pointer;">
        <input type="radio" name="courier_type" id="courier_none" value="none" checked> 
        არ გამოიყენება
    </label>
</div>
                                
                                <textarea name="comment" class="form-control" rows="3" placeholder="Notes..." style="font-size:12px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-gray-light">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-flat" style="padding: 6px 40px; font-weight: bold;">💾 Save Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('customers.form')