@php
    $isAdmin = auth()->check() && auth()->user()->role == 'admin';
@endphp

<style>
    .modal-sale-card { border: 1px solid #eee; border-radius: 10px; padding: 12px; background: #fff; margin-bottom: 12px; }
    .section-title { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
    .price-badge { font-size: 14px; padding: 8px; border-radius: 8px; text-align: center; font-weight: 700; }
    .select2-container--bootstrap-5 .select2-selection { font-size: 13px; }
</style>

<div class="modal fade" id="modal-sale" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:15px; overflow:hidden;">
            <form id="form-sale-content" method="post" enctype="multipart/form-data">
                @csrf @method('POST')
                
                {{-- Hidden Inputs --}}
                <input type="hidden" name="id" id="id">
                <input type="hidden" name="order_type" value="sale">
                <input type="hidden" name="price_georgia" id="price_georgia_sale">
                <input type="hidden" name="price_usa" id="price_usa_sale">
                <input type="hidden" id="courier_price_tbilisi" name="courier_price_tbilisi" value="0">
                <input type="hidden" id="db_tbilisi_price" value="{{ $courier->tbilisi_price ?? 6 }}">

                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-cart-plus-fill"></i> 
                        <span id="modal-sale-title">ახალი გაყიდვა</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body bg-light p-3" style="max-height: 75vh; overflow-y: auto;">
                    <div class="row g-3">
                        
                        {{-- მარცხენა მხარე --}}
                        <div class="col-12 col-md-8">
                            
                            {{-- Section 1: Product --}}
                            <div class="modal-sale-card shadow-sm">
                                <div class="section-title"><i class="bi bi-box-seam"></i> პროდუქტის შერჩევა</div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small fw-bold">პროდუქტი</label>
                                        <select name="product_id" id="product_id_sale" class="form-select select2" required>
                                            <option value="">— აირჩიეთ —</option>
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
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-bold">ზომა</label>
                                        <select name="product_size" id="size_sale" class="form-select shadow-sm" required>
                                            <option value="">—</option>
                                        </select>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-bold text-success">₾ GEL</label>
                                        <div id="price_georgia_text" class="price-badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">0</div>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-bold text-primary">$ USD</label>
                                        <div id="price_usa_text" class="price-badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">0</div>
                                    </div>
                                </div>

                                {{-- Stock Info --}}
                                <div id="sale_stock_info" class="mt-3 p-2 rounded-3 border-start border-4 border-info bg-white shadow-sm" style="display:none; font-size:12px;">
                                    <div class="d-flex justify-content-between">
                                        <span>📦 საწყობი: <b id="sale_si_physical">0</b></span>
                                        <span>🚚 გზაში: <b id="sale_si_incoming">0</b></span>
                                        <span>🔒 ჯავშანი: <b id="sale_si_reserved">0</b></span>
                                        <span class="text-success">✅ ხელმისაწვდომია: <b id="sale_si_available">0</b></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Customer --}}
                            <div class="modal-sale-card shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="section-title m-0"><i class="bi bi-person-circle"></i> კლიენტი</div>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-0" onclick="openCustomerCreate()" style="font-size:11px;">
                                        + ახალი კლიენტი
                                    </button>
                                </div>
                                <select name="customer_id" id="customer_id_sale" class="form-select select2" required>
                                    <option value="">— აირჩიეთ კლიენტი —</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            data-address="{{ $customer->address }}"
                                            data-city-id="{{ $customer->city_id }}"
                                            data-tel="{{ $customer->tel }}"
                                            data-alt="{{ $customer->alternative_tel }}">
                                            {{ $customer->name }} ({{ $customer->tel }})
                                        </option>
                                    @endforeach
                                </select>

                                <div id="customer_info_fields" class="mt-2 p-2 border rounded bg-white shadow-sm" style="display:none; font-size:12px;">
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <div class="mb-1"><i class="bi bi-telephone"></i> <span id="customer_tel" class="fw-bold"></span></div>
                                            <input type="text" id="customer_alt_tel_input" name="order_alt_tel" class="form-control form-control-sm" placeholder="ალტერნატიული ნომერი">
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="mb-1"><i class="bi bi-geo-alt"></i> მისამართი:</div>
                                            <input type="text" id="customer_address_input" name="order_address" class="form-control form-control-sm" placeholder="ქუჩა, კორპუსი, ბინა">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 3: Admin Finance --}}
                            @if($isAdmin)
                            <div class="modal-sale-card border-warning bg-warning bg-opacity-10 shadow-sm">
                                <div class="section-title text-dark"><i class="bi bi-cash-stack"></i> ფინანსური ნაწილი</div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white">ფასდაკლება</span>
                                            <input type="number" name="discount" id="discount_sale" class="form-control fw-bold text-danger" value="0">
                                            <span class="input-group-text bg-white">₾</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-8 text-end text-muted small">
                                        ჯამური გადასახდელი: <span id="sale_summary_text" class="fw-bold text-dark fs-6">—</span>
                                    </div>
                                </div>
                                <div class="row g-1 mt-2">
                                    <div class="col-3">
                                        <input type="number" name="paid_tbc" class="form-control form-control-sm" placeholder="TBC" step="0.01">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="paid_bog" class="form-control form-control-sm" placeholder="BOG" step="0.01">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="paid_lib" class="form-control form-control-sm" placeholder="LIB" step="0.01">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="paid_cash" class="form-control form-control-sm" placeholder="CASH" step="0.01">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- მარჯვენა მხარე --}}
                        <div class="col-12 col-md-4">
                            <div class="modal-sale-card h-100 shadow-sm">
                                <div class="section-title"><i class="bi bi-image"></i> პროდუქტის ფოტო</div>
                                <div class="text-center border rounded mb-3 bg-light d-flex align-items-center justify-content-center" style="height:150px; overflow:hidden;">
                                    <img id="target_image" class="img-fluid" style="display:none; max-height:100%;">
                                    <div id="no_image_text" class="text-muted small">
                                        <i class="bi bi-image-fill fs-2 d-block"></i>
                                        არ არის ფოტო
                                    </div>
                                </div>

                                <div class="section-title"><i class="bi bi-truck"></i> კურიერი</div>
                                <div class="bg-light p-2 rounded border shadow-sm">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_tbilisi" value="tbilisi">
                                        <label class="form-check-label small" for="courier_tbilisi">თბილისი (+{{ $courier->tbilisi_price ?? 6 }} ₾)</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_region" value="region">
                                        <label class="form-check-label small" for="courier_region">რაიონი (+{{ $courier->region_price ?? 9 }} ₾)</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_village" value="village">
                                        <label class="form-check-label small" for="courier_village">სოფელი (+{{ $courier->village_price ?? 13 }} ₾)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_none" value="none" checked>
                                        <label class="form-check-label small text-muted" for="courier_none">არ გამოიყენება</label>
                                    </div>
                                </div>
                                
                                <label class="form-label small fw-bold mt-3 mb-1">კომენტარი</label>
                                <textarea name="comment" class="form-control form-control-sm shadow-sm" rows="3" placeholder="დამატებითი შენიშვნა..."></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm rounded-pill">
                        <i class="bi bi-check-lg"></i> შენახვა
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('customers.form')