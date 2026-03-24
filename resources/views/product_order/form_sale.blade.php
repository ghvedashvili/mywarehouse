@php
    $isAdmin = auth()->user()->role === 'admin';
@endphp

<div class="modal fade" id="modal-sale" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">

            <form id="form-sale-content" method="post">
                {{ csrf_field() }} {{ method_field('POST') }}

                <input type="hidden" name="id"         id="id">
                <input type="hidden" name="order_type" value="sale">
                <input type="hidden" name="status_id"  value="1">
                <input type="hidden" name="courier_id" value="1">

                {{-- HEADER --}}
                <div class="modal-header py-2 px-3 bg-primary text-white">
                    <h6 class="modal-title mb-0">🛒 Add Sale</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body p-3">

                    {{-- ══════════════════════════════════
                         PRODUCT BLOCK
                    ══════════════════════════════════ --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-1 px-3 border-bottom">
                            <small class="font-weight-bold text-uppercase text-muted">📦 Product</small>
                        </div>
                        <div class="card-body p-2">
                            <div class="form-row align-items-end">

                                <div class="col">
                                    <label class="mb-1 small text-muted">Product</label>
                                    <select name="product_id" id="product_id_sale" class="form-control form-control-sm">
                                        <option value="">— Product —</option>
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

                                <div class="col-auto" style="min-width:90px">
                                    <label class="mb-1 small text-muted">Size</label>
                                    <select name="product_size" id="size_sale" class="form-control form-control-sm">
                                        <option value="">— Size —</option>
                                    </select>
                                </div>

                                <div class="col-auto" style="min-width:75px">
                                    <label class="mb-1 small text-muted">₾ Price</label>
                                    <p id="price_georgia_text" class="form-control form-control-sm text-center mb-0 font-weight-bold bg-white">0</p>
                                    <input type="hidden" name="price_georgia" id="price_georgia_sale">
                                </div>

                                <div class="col-auto">
                                    <div class="border rounded d-flex align-items-center justify-content-center bg-white"
                                         style="width:56px;height:48px;overflow:hidden;">
                                        <img id="target_image" class="img-fluid" style="display:none;max-height:46px;">
                                        <small id="no_image_text" class="text-muted" style="font-size:9px;">No img</small>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ══════════════════════════════════
                         CUSTOMER BLOCK
                    ══════════════════════════════════ --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-1 px-3 border-bottom d-flex align-items-center justify-content-between">
                            <small class="font-weight-bold text-uppercase text-muted">👤 Customer</small>
                            <button type="button" class="btn btn-link btn-sm p-0 text-primary" style="font-size:12px;" onclick="openCustomerCreate()">+ Add New</button>
                        </div>
                        <div class="card-body p-2">

                            <select name="customer_id" id="customer_id_sale" class="form-control form-control-sm mb-2" style="width:100%">
                                <option value="">— Choose Customer —</option>
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

                            <div id="customer_info_fields" style="display:none;">
                                <div class="d-flex flex-wrap" style="gap:4px;">
                                    <span class="badge badge-secondary" style="font-size:11px;font-weight:400;">
                                        📍 <span id="customer_address"></span>
                                    </span>
                                    <span class="badge badge-secondary" style="font-size:11px;font-weight:400;">
                                        📞 <span id="customer_tel"></span>
                                    </span>
                                    <span class="badge badge-secondary" style="font-size:11px;font-weight:400;">
                                        📱 <span id="customer_alt_tel"></span>
                                    </span>
                                    <span class="badge badge-secondary" style="font-size:11px;font-weight:400;">
                                        📝 <span id="customer_comment"></span>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ══════════════════════════════════
                         FINANCE BLOCK  (admin only)
                    ══════════════════════════════════ --}}
                    @if($isAdmin)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-1 px-3 border-bottom">
                            <small class="font-weight-bold text-uppercase text-muted">💳 Finance</small>
                        </div>
                        <div class="card-body p-2">

                            {{-- $ Price / Discount / Status — one row --}}
                            <div class="form-row align-items-end mb-2">
                                <div class="col">
                                    <label class="mb-1 small text-muted">$ Price</label>
                                    <p id="price_usa_text" class="form-control form-control-sm text-center mb-0 font-weight-bold bg-white">0</p>
                                    <input type="hidden" name="price_usa" id="price_usa_sale">
                                </div>
                                <div class="col">
                                    <label class="mb-1 small text-muted">Discount</label>
                                    <input type="number" name="discount" id="discount_sale" class="form-control form-control-sm" value="0">
                                </div>
                                <div class="col-md-5">
                                    <label class="mb-1 small text-muted">სტატუსი</label>
                                    <select name="status_id" id="status_id_sale" class="form-control form-control-sm">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Bank payments — one row --}}
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">TBC</span></div>
                                <input type="number" name="paid_tbc" class="form-control" placeholder="0">
                                <div class="input-group-prepend"><span class="input-group-text">BOG</span></div>
                                <input type="number" name="paid_bog" class="form-control" placeholder="0">
                                <div class="input-group-prepend"><span class="input-group-text">Lib</span></div>
                                <input type="number" name="paid_lib" class="form-control" placeholder="0">
                                <div class="input-group-prepend"><span class="input-group-text">Cash</span></div>
                                <input type="number" name="paid_cash" class="form-control" placeholder="0">
                            </div>

                        </div>
                    </div>
                    @endif

                    {{-- ══════════════════════════════════
                         DELIVERY & NOTES
                    ══════════════════════════════════ --}}
                    <div class="card border-0 shadow-sm mb-2">
                        <div class="card-header bg-light py-1 px-3 border-bottom">
                            <small class="font-weight-bold text-uppercase text-muted">🚚 Delivery & Notes</small>
                        </div>
                        <div class="card-body p-2">

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" id="is_local_courier" name="courier_servise_local" value="1" class="form-check-input">
                                    <label class="form-check-label small" for="is_local_courier">
                                        Tbilisi Courier <span class="text-muted">(+{{ $courier->tbilisi_price ?? 6 }} ₾)</span>
                                    </label>
                                </div>
                                <small>Status: <strong id="sale_summary_text" class="text-warning">Waiting...</strong></small>
                            </div>

                            <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Comment..."></textarea>

                        </div>
                    </div>

                </div>

                <div class="modal-footer py-2 px-3">
                    <button type="submit" class="btn btn-sm btn-success px-4">💾 Save</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Close</button>
                </div>

            </form>
        </div>
    </div>
</div>


{{-- STATUS QUICK-CHANGE MODAL --}}
<div class="modal fade" id="modal-status" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" style="margin-top:160px;">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-dark text-white py-2 px-3">
                <h6 class="modal-title mb-0"><i class="fa fa-tag mr-1"></i> სტატუსის შეცვლა</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body py-3 px-3">
                <input type="hidden" id="status_order_id">
                <label class="small text-muted mb-1">აირჩიე სტატუსი</label>
                <select id="quick_status_select" class="form-control form-control-sm">
                    @foreach($statuses as $status)
                        <option value="{{ $status->id }}" data-color="{{ $status->color }}">
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">გაუქმება</button>
                <button type="button" class="btn btn-success btn-sm px-3" onclick="saveQuickStatus()">
                    <i class="fa fa-check mr-1"></i>შენახვა
                </button>
            </div>

        </div>
    </div>
</div>

@include('customers.form')