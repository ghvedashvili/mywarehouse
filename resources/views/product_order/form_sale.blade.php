@php
    $isAdmin = auth()->check() && auth()->user()->role == 'admin';
@endphp

<style>
    .modal-sale-card {
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        margin-bottom: 10px;
    }
    .section-title {
        font-size: 11px;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .price-badge {
        font-size: 14px;
        padding: 6px 8px;
        border-radius: 8px;
        text-align: center;
        font-weight: 700;
    }
    .sale-preview-box {
        height: 90px;
        overflow: hidden;
    }
    @media (min-width: 768px) {
        .sale-preview-box { height: 140px; }
    }
    .courier-options {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .courier-options .form-check {
        margin: 0;
        white-space: nowrap;
    }
    .sale-item-row {
        background: #fafafa;
        border: 1px solid #e0e0e0 !important;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 8px;
    }
    .sale-item-row .sale-price-gel,
    .sale-item-row .sale-price-usd {
        font-size: 12px;
        font-weight: 700;
        text-align: center;
        padding: 4px;
    }
</style>

<div class="modal fade" id="modal-sale" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-dark text-white border-0 py-2">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-cart-plus-fill"></i>
                    <span id="modal-sale-title">ახალი გაყიდვა</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body bg-light p-3">
                <form id="form-sale-content" method="post" enctype="multipart/form-data">
                    @csrf @method('POST')

                    <input type="hidden" name="id"    id="id">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" id="db_tbilisi_price" value="{{ $courier->tbilisi_price ?? 6 }}">

                    {{-- Hidden product options template (cloned by JS for each row) --}}
                    <select id="product-options-template" style="display:none" aria-hidden="true">
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

                    <div class="row g-2">

                        {{-- ═══ LEFT: პროდუქტები + კლიენტი + ფინანსები ═══ --}}
                        <div class="col-12 col-md-8">

                            {{-- Section 1: Products --}}
                            <div class="modal-sale-card shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="section-title m-0"><i class="bi bi-box-seam"></i> პროდუქტები</div>
                                    <button type="button" class="btn btn-outline-success btn-sm py-0 px-2" id="add-sale-line" style="font-size:11px;">
                                        <i class="bi bi-plus-circle"></i> დამატება
                                    </button>
                                </div>
                                <div id="sale-items-container"></div>
                            </div>

                            {{-- Section 2: Customer --}}
                            <div class="modal-sale-card shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="section-title m-0"><i class="bi bi-person-circle"></i> კლიენტი</div>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-0"
                                            onclick="openCustomerCreate()" style="font-size:11px;">
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
                                        <div class="col-12 col-sm-6">
                                            <div class="mb-1"><i class="bi bi-telephone"></i> <span id="customer_tel" class="fw-bold"></span></div>
                                            <input type="text" id="customer_alt_tel_input" name="order_alt_tel"
                                                   class="form-control form-control-sm" placeholder="ალტერნატიული ნომერი">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <div class="mb-1"><i class="bi bi-geo-alt"></i> მისამართი:</div>
                                            <input type="text" id="customer_address_input" name="order_address"
                                                   class="form-control form-control-sm" placeholder="ქუჩა, კორპუსი, ბინა">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 3: Admin Finance --}}
                            @if($isAdmin)
                            <div id="sale-finance-section" class="modal-sale-card border-warning bg-warning bg-opacity-10 shadow-sm" style="display:none;">
                                <div class="section-title text-dark"><i class="bi bi-cash-stack"></i> ფინანსური ნაწილი</div>
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-12 text-muted small">
                                        ჯამური: <span id="sale_summary_text" class="fw-bold text-dark fs-6">—</span>
                                    </div>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6 col-sm-3">
                                        <input type="number" name="paid_tbc" class="form-control form-control-sm" placeholder="TBC" step="0.01">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <input type="number" name="paid_bog" class="form-control form-control-sm" placeholder="BOG" step="0.01">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <input type="number" name="paid_lib" class="form-control form-control-sm" placeholder="LIB" step="0.01">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <input type="number" name="paid_cash" class="form-control form-control-sm" placeholder="CASH" step="0.01">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- ═══ RIGHT: ფოტო + კურიერი + კომენტარი ═══ --}}
                        <div class="col-12 col-md-4">
                            <div class="modal-sale-card shadow-sm">

                                <div class="section-title"><i class="bi bi-image"></i> ფოტო</div>
                                <div class="text-center border rounded mb-2 bg-light d-flex align-items-center justify-content-center sale-preview-box">
                                    <img id="target_image" class="img-fluid" style="display:none; max-height:100%;">
                                    <div id="no_image_text" class="text-muted small">
                                        <i class="bi bi-image-fill fs-3 d-block"></i>
                                        არ არის ფოტო
                                    </div>
                                </div>

                                <div class="section-title mt-1"><i class="bi bi-truck"></i> კურიერი</div>
                                <div class="courier-options bg-light p-2 rounded border">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_tbilisi" value="tbilisi">
                                        <label class="form-check-label small" for="courier_tbilisi">თბილისი (+{{ $courier->tbilisi_price ?? 6 }}₾)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_region" value="region">
                                        <label class="form-check-label small" for="courier_region">რაიონი (+{{ $courier->region_price ?? 9 }}₾)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_village" value="village">
                                        <label class="form-check-label small" for="courier_village">სოფელი (+{{ $courier->village_price ?? 13 }}₾)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="courier_type" id="courier_none" value="none" checked>
                                        <label class="form-check-label small text-muted" for="courier_none">არ გამოიყენება</label>
                                    </div>
                                </div>

                                <label class="form-label small fw-bold mt-2 mb-1">კომენტარი</label>
                                <textarea name="comment" class="form-control form-control-sm" rows="2"
                                          placeholder="დამატებითი შენიშვნა..."></textarea>
                            </div>
                        </div>

                    </div>{{-- /row --}}
                </form>
            </div>{{-- /modal-body --}}

            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="submit" form="form-sale-content" class="btn btn-success px-4 fw-bold">
                    <i class="bi bi-check-lg me-1"></i> შენახვა
                </button>
            </div>

        </div>
    </div>
</div>

@include('customers.form')
