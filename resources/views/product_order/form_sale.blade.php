@php $isAdmin = auth()->check() && auth()->user()->role == 'admin'; @endphp

<style>
/* ── Modal shell ── */
#modal-sale .modal-content { border-radius: 16px; overflow: hidden; }
#modal-sale .modal-header  { background: linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%); padding: 14px 20px; }
#modal-sale .modal-body    { background: #f4f6fb; padding: 18px; }
#modal-sale .modal-footer  { background: #fff; border-top: 1px solid #e9ecef; padding: 10px 18px; }

/* ── Section cards ── */
.sc { background:#fff; border-radius:12px; padding:14px 16px; margin-bottom:12px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.sc-title {
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px;
    color:#8e9bb5; margin-bottom:12px; display:flex; align-items:center; gap:6px;
}
.sc-title i { font-size:13px; }

/* ── Product item row ── */
.sale-item-row {
    background: #fafbff;
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    padding: 10px 12px 8px;
    margin-bottom: 8px;
    transition: border-color .2s;
}
.sale-item-row:focus-within { border-color: #6c8ebf; }

.sale-col-label {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    color: #aab; letter-spacing: .5px; margin-bottom: 3px;
}

/* Price badges */
.price-pill-gel {
    background: #e8faf0; color: #1a7a4a; border: 1px solid #b7e4c7;
    border-radius: 20px; padding: 0 10px; font-size: 12px; font-weight: 700;
    white-space: nowrap; text-align: center; min-width: 60px;
    display: inline-flex; align-items: center; justify-content: center;
    height: 31px; box-sizing: border-box;
}
.price-pill-usd {
    background: #eaf2ff; color: #1a4fa0; border: 1px solid #b8d0f5;
    border-radius: 20px; padding: 0 10px; font-size: 12px; font-weight: 700;
    white-space: nowrap; text-align: center; min-width: 60px;
    display: inline-flex; align-items: center; justify-content: center;
    height: 31px; box-sizing: border-box;
}

/* Stock indicator */
.sale-row-stock { font-size: 11px; margin-top: 6px; padding: 4px 8px; background: #f0f4ff; border-radius: 6px; }

/* ── Customer info block ── */
#customer_info_fields {
    background: #f8f9ff; border: 1.5px solid #dde3f5; border-radius: 10px;
    padding: 12px; margin-top: 10px;
}
.cust-field-label { font-size: 10px; font-weight: 700; color: #8e9bb5; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px; }
.cust-tel-display {
    background: #fff; border: 1.5px solid #e0e4f0; border-radius: 8px;
    padding: 5px 10px; font-weight: 700; font-size: 13px; color: #2d3a5a;
}

/* ── Courier pills ── */
.courier-pill-group { display: flex; flex-wrap: wrap; gap: 6px; }
.courier-pill-group input[type=radio] { display: none; }
.courier-pill-group label {
    padding: 5px 12px; border-radius: 20px; border: 1.5px solid #d0d7e8;
    font-size: 12px; font-weight: 600; color: #5a6480; cursor: pointer;
    background: #fff; transition: all .15s; white-space: nowrap;
}
.courier-pill-group input[type=radio]:checked + label {
    background: #0f3460; border-color: #0f3460; color: #fff;
}
.courier-pill-group label:hover { border-color: #0f3460; color: #0f3460; }

/* ── Photo box ── */
.sale-photo-box {
    height: 130px; border-radius: 10px; border: 2px dashed #d0d7e8;
    display: flex; align-items: center; justify-content: center;
    background: #f8f9ff; overflow: hidden; margin-bottom: 12px;
}
.sale-photo-box img { max-height: 100%; max-width: 100%; object-fit: cover; border-radius: 8px; }

/* ── Save button ── */
#btn-sale-save {
    background: linear-gradient(135deg,#1a7a4a,#28a063);
    border: none; border-radius: 10px; padding: 9px 28px;
    font-weight: 700; font-size: 14px; letter-spacing: .3px;
}
#btn-sale-save:hover { opacity: .92; }

/* ── Scrollable stock info ── */
.sale-items-wrapper { max-height: 320px; overflow-y: auto; padding-right: 2px; }
</style>

<div class="modal fade" id="modal-sale" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg">

            {{-- HEADER --}}
            <div class="modal-header border-0">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-cart-plus-fill text-white" style="font-size:17px;"></i>
                    </div>
                    <div>
                        <div id="modal-sale-title" class="text-white fw-bold" style="font-size:15px; line-height:1.2;">ახალი გაყიდვა</div>
                        <div class="text-white-50" style="font-size:11px;">გაყიდვის ორდერი</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body">
                <form id="form-sale-content" method="post" enctype="multipart/form-data">
                    @csrf @method('POST')
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" id="db_tbilisi_price" value="{{ $courier->tbilisi_price ?? 6 }}">

                    {{-- Hidden product template --}}
                    <select id="product-options-template" style="display:none" aria-hidden="true">
                        <option value="">— აირჩიეთ —</option>
                        @foreach($all_products as $product)
                            <option value="{{ $product->id }}"
                                data-price-ge="{{ $product->price_geo }}"
                                data-price-us="{{ $product->price_usa }}"
                                data-sizes="{{ $product->sizes }}"
                                data-image="{{ $product->image_url ?? '' }}"
                                data-bundle-id="{{ $product->bundle_id ?? '' }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="row g-3">

                        {{-- ══════ LEFT (8 cols) ══════ --}}
                        <div class="col-12 col-md-8">

                            {{-- Products --}}
                            <div class="sc">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="sc-title m-0"><i class="bi bi-box-seam-fill"></i> პროდუქტები</div>
                                    <button type="button" id="add-sale-line"
                                            class="btn btn-sm fw-semibold"
                                            style="background:#e8faf0;color:#1a7a4a;border:1.5px solid #b7e4c7;border-radius:20px;font-size:11px;padding:3px 12px;">
                                        <i class="bi bi-plus-lg me-1"></i>დამატება
                                    </button>
                                </div>
                                <div class="sale-items-wrapper">
                                    <div id="sale-items-container"></div>
                                </div>
                            </div>

                            {{-- Customer --}}
                            <div class="sc">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="sc-title m-0"><i class="bi bi-person-circle"></i> კლიენტი</div>
                                    <button type="button" onclick="openCustomerCreate()"
                                            class="btn btn-sm fw-semibold"
                                            style="background:#eaf2ff;color:#1a4fa0;border:1.5px solid #b8d0f5;border-radius:20px;font-size:11px;padding:3px 12px;">
                                        <i class="bi bi-person-plus me-1"></i>ახალი
                                    </button>
                                </div>

                                <select name="customer_id" id="customer_id_sale" class="form-select form-select-sm select2" required>
                                    <option value="">— აირჩიეთ კლიენტი —</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            data-address="{{ $customer->address }}"
                                            data-city-id="{{ $customer->city_id }}"
                                            data-city="{{ $customer->city->name ?? '' }}"
                                            data-tel="{{ $customer->tel }}"
                                            data-alt="{{ $customer->alternative_tel }}"
                                            data-comment="{{ $customer->comment ?? '' }}">
                                            {{ $customer->name }} ({{ $customer->tel }})
                                        </option>
                                    @endforeach
                                </select>

                                <div id="customer_info_fields" style="display:none;">
                                    <div class="row g-2 mt-1">
                                        <div class="col-6">
                                            <div class="cust-field-label"><i class="bi bi-telephone me-1"></i>ძირითადი ტელ.</div>
                                            <div class="cust-tel-display" id="customer_tel"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="cust-field-label"><i class="bi bi-telephone-plus me-1"></i>ალტ. ტელეფონი</div>
                                            <input type="text" id="customer_alt_tel_input" name="order_alt_tel"
                                                   class="form-control form-control-sm" placeholder="სურვილისამებრ"
                                                   style="border-radius:8px;border:1.5px solid #e0e4f0;">
                                        </div>
                                        <div class="col-6">
                                            <div class="cust-field-label"><i class="bi bi-building me-1"></i>ქალაქი</div>
                                            <select id="customer_city_select" name="order_city_id"
                                                    class="form-select form-select-sm"
                                                    style="border-radius:8px;border:1.5px solid #e0e4f0;">
                                                <option value="">-- ქალაქი --</option>
                                                @foreach($cities as $city)
                                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <div class="cust-field-label"><i class="bi bi-geo-alt me-1"></i>მისამართი</div>
                                            <input type="text" id="customer_address_input" name="order_address"
                                                   class="form-control form-control-sm" placeholder="ქუჩა, კორპუსი, ბინა"
                                                   style="border-radius:8px;border:1.5px solid #e0e4f0;">
                                        </div>
                                        <div id="customer_comment_wrap" class="col-12" style="display:none;">
                                            <div class="rounded px-3 py-2 d-flex align-items-center gap-2"
                                                 style="background:#fffbea;border:1.5px solid #ffe58f;font-size:12px;color:#7d6608;">
                                                <i class="bi bi-chat-left-text-fill"></i>
                                                <span id="customer_comment"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- /col left --}}

                        {{-- ══════ RIGHT (4 cols) ══════ --}}
                        <div class="col-12 col-md-4">
                            <div class="sc">

                                {{-- Photo --}}
                                <div class="sc-title"><i class="bi bi-image-fill"></i> პროდუქტის ფოტო</div>
                                <div class="sale-photo-box">
                                    <img id="target_image" style="display:none;">
                                    <div id="no_image_text" class="text-center text-muted">
                                        <i class="bi bi-image-fill d-block mb-1" style="font-size:28px;opacity:.3;"></i>
                                        <span style="font-size:11px;">ფოტო არ არის</span>
                                    </div>
                                </div>

                                {{-- Courier --}}
                                <div class="sc-title mt-2"><i class="bi bi-truck-front-fill"></i> მიწოდება</div>
                                <div class="courier-pill-group mb-3">
                                    <input type="radio" name="courier_type" id="courier_none"     value="none"    checked>
                                    <label for="courier_none">არა</label>

                                    <input type="radio" name="courier_type" id="courier_tbilisi"  value="tbilisi">
                                    <label for="courier_tbilisi">თბ. +{{ $courier->tbilisi_price ?? 6 }}₾</label>

                                    <input type="radio" name="courier_type" id="courier_region"   value="region">
                                    <label for="courier_region">რაიონი +{{ $courier->region_price ?? 9 }}₾</label>

                                    <input type="radio" name="courier_type" id="courier_village"  value="village">
                                    <label for="courier_village">სოფელი +{{ $courier->village_price ?? 13 }}₾</label>
                                </div>

                                {{-- ── გადახდილი თანხები (edit-ისთვის) ── --}}
                                <input type="hidden" name="paid_tbc"  value="0">
                                <input type="hidden" name="paid_bog"  value="0">
                                <input type="hidden" name="paid_lib"  value="0">
                                <input type="hidden" name="paid_cash" value="0">
                                
                                {{-- Comment --}}
                                <div class="sc-title"><i class="bi bi-chat-left-dots-fill"></i> კომენტარი</div>
                                <textarea name="comment" class="form-control form-control-sm" rows="3"
                                          placeholder="დამატებითი შენიშვნა..."
                                          style="border-radius:8px;border:1.5px solid #e0e4f0;resize:none;font-size:12px;"></textarea>

                            </div>
                        </div>{{-- /col right --}}

                    </div>{{-- /row --}}
                </form>
            </div>{{-- /body --}}

            {{-- FOOTER --}}
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-light fw-semibold px-4" data-bs-dismiss="modal"
                        style="border-radius:10px;border:1.5px solid #dee2e6;">
                    <i class="bi bi-x-lg me-1"></i>გაუქმება
                </button>
                <button type="submit" form="form-sale-content" id="btn-sale-save" class="btn text-white">
                    <i class="bi bi-check2-circle me-1"></i>შენახვა
                </button>
            </div>

        </div>
    </div>
</div>

@include('customers.form')
