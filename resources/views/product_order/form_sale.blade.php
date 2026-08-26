@php $isAdmin = auth()->check() && auth()->user()->role == 'admin'; @endphp

<style>
/* ══════════════════════════════════════════════════════
   SALE MODAL — Design System
   ══════════════════════════════════════════════════════ */

/* ── Desktop: entry animation ── */
#modal-sale.fade .modal-dialog {
    transform: scale(.97) translateY(10px);
    opacity: 0;
    transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .22s ease;
}
#modal-sale.show .modal-dialog {
    transform: scale(1) translateY(0);
    opacity: 1;
}

/* ── Modal shell ── */
#modal-sale .modal-content {
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(100dvh - 80px);
    border: 0;
    box-shadow: 0 24px 72px rgba(0,0,0,.22), 0 0 0 1px rgba(255,255,255,.06);
}

/* ── Header ── */
#modal-sale .modal-header {
    background: linear-gradient(135deg, #0d1b3e 0%, #162754 55%, #0f3460 100%);
    padding: 18px 22px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    border-bottom: 0;
}
#modal-sale .modal-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 80% 0%, rgba(100,160,255,.12) 0%, transparent 60%);
    pointer-events: none;
}

/* ── Body ── */
#modal-sale .modal-body {
    background: #eef1f9;
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

/* ── Footer ── */
#modal-sale .modal-footer {
    background: #fff;
    border-top: 1px solid #e4e8f2;
    padding: 12px 20px;
    flex-shrink: 0;
}

/* ══════════════════════════════════════════════════════
   MOBILE — BOTTOM SHEET
   ══════════════════════════════════════════════════════ */
@media (max-width: 575.98px) {

    /* Anchor to bottom edge */
    #modal-sale {
        align-items: flex-end !important;
        padding: 0 !important;
    }

    /* Slide-up animation */
    #modal-sale.fade .modal-dialog {
        transform: translateY(100%) !important;
        opacity: 1 !important;
        transition: transform .4s cubic-bezier(.32,1.08,.64,1) !important;
    }
    #modal-sale.show .modal-dialog {
        transform: translateY(0) !important;
        opacity: 1 !important;
    }

    /* Sheet dimensions */
    #modal-sale .modal-dialog {
        margin: 0;
        max-width: 100%;
        width: 100%;
        height: auto;
        max-height: 92dvh;
    }

    /* Sheet shape */
    #modal-sale .modal-content {
        border-radius: 22px 22px 0 0;
        height: auto;
        max-height: 92dvh;
        box-shadow: 0 -8px 40px rgba(0,0,0,.2);
    }

    /* Drag handle */
    #modal-sale .modal-header::before {
        content: '';
        position: absolute;
        top: calc(10px + env(safe-area-inset-top));
        left: 50%;
        transform: translateX(-50%);
        width: 36px;
        height: 4px;
        border-radius: 2px;
        background: rgba(255,255,255,.28);
        z-index: 1;
    }

    /* Notch / Dynamic Island */
    #modal-sale .modal-header {
        padding-top: calc(26px + env(safe-area-inset-top));
    }

    /* Home indicator */
    #modal-sale .modal-footer {
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
    }
}

/* ── Section cards ── */
.sc {
    background: #fff;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 4px 12px rgba(0,0,0,.04);
    border: 1px solid rgba(0,0,0,.04);
}
.sc-title {
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px;
    color: #8e9bb5; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;
}
.sc-title i { font-size: 13px; }

/* ── Product item row ── */
.sale-item-row {
    background: #f7f9ff;
    border: 1.5px solid #e6e9f5;
    border-radius: 12px;
    padding: 10px 12px 8px;
    margin-bottom: 8px;
    transition: border-color .18s, box-shadow .18s;
}
.sale-item-row:focus-within {
    border-color: #4e7fd5;
    box-shadow: 0 0 0 3px rgba(78,127,213,.1);
}

.sale-col-label {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    color: #aab; letter-spacing: .5px; margin-bottom: 3px;
}

/* ── Price badges ── */
.price-pill-gel {
    background: linear-gradient(135deg,#e6faf1,#d4f5e4);
    color: #176b41; border: 1px solid #b7e4c7;
    border-radius: 20px; padding: 0 10px; font-size: 12px; font-weight: 700;
    white-space: nowrap; text-align: center; min-width: 60px;
    display: inline-flex; align-items: center; justify-content: center;
    height: 31px; box-sizing: border-box;
}
.price-pill-usd {
    background: linear-gradient(135deg,#e8f1ff,#d8e8ff);
    color: #1a4fa0; border: 1px solid #b8d0f5;
    border-radius: 20px; padding: 0 10px; font-size: 12px; font-weight: 700;
    white-space: nowrap; text-align: center; min-width: 60px;
    display: inline-flex; align-items: center; justify-content: center;
    height: 31px; box-sizing: border-box;
}

/* ── Stock indicator ── */
.sale-row-stock { font-size: 11px; margin-top: 6px; padding: 4px 10px; background: #eef2ff; border-radius: 8px; }

/* ── Customer info block ── */
#customer_info_fields {
    background: #f6f8ff; border: 1.5px solid #dde3f5; border-radius: 12px;
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
    padding: 5px 13px; border-radius: 20px; border: 1.5px solid #d0d7e8;
    font-size: 12px; font-weight: 600; color: #5a6480; cursor: pointer;
    background: #fff; transition: all .15s; white-space: nowrap;
}
.courier-pill-group input[type=radio]:checked + label {
    background: linear-gradient(135deg,#162754,#0f3460);
    border-color: #0f3460; color: #fff;
    box-shadow: 0 2px 8px rgba(15,52,96,.3);
}
.courier-pill-group label:hover { border-color: #4e7fd5; color: #1a4fa0; }

/* ── Photo box ── */
.sale-photo-box {
    height: 130px; border-radius: 12px; border: 2px dashed #c8d2e8;
    display: flex; align-items: center; justify-content: center;
    background: #f6f8ff; overflow: hidden; margin-bottom: 12px;
}
.sale-photo-box img { max-height: 100%; max-width: 100%; object-fit: cover; border-radius: 10px; }

/* ── Save button ── */
#btn-sale-save {
    background: linear-gradient(135deg,#176b41,#22a05a);
    border: none; border-radius: 12px; padding: 10px 28px;
    font-weight: 700; font-size: 14px; letter-spacing: .3px;
    box-shadow: 0 4px 14px rgba(23,107,65,.3);
    transition: opacity .15s, box-shadow .15s;
}
#btn-sale-save:hover { opacity: .92; box-shadow: 0 6px 18px rgba(23,107,65,.38); }

/* ── Products scroll area ── */
.sale-items-wrapper { max-height: 320px; overflow-y: auto; padding-right: 2px; }
@media (max-width: 767px) {
    .sale-items-wrapper { max-height: none; overflow-y: visible; }
    .sale-photo-box { display: none; }
}
</style>

<div class="modal fade" id="modal-sale" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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
                <form id="form-sale-content" method="post" enctype="multipart/form-data" autocomplete="off">
                    @csrf @method('POST')
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="order_type" value="sale">
                    <input type="hidden" id="db_tbilisi_price" value="{{ $courier->tbilisi_price ?? 6 }}">
                    <input type="hidden" name="warehouse_sale" id="warehouse_sale_flag" value="0">

                    {{-- Hidden product template --}}
                    <select id="product-options-template" style="display:none" aria-hidden="true">
                        <option value="">— აირჩიეთ —</option>
                        @foreach($all_products as $product)
                            <option value="{{ $product->id }}"
                                data-price-ge="{{ $product->price_geo }}"
                                data-price-us="{{ $product->price_usa }}"
                                data-sizes="{{ $product->sizes }}"
                                data-image="{{ $product->image_url ?? '' }}"
                                data-bundle-id="{{ $product->bundle_id ?? '' }}"
                                data-code="{{ $product->product_code ?? '' }}"
                                data-warehouse-only="{{ $product->warehouse_only ? '1' : '0' }}"
                                data-divisible="{{ $product->category?->is_divisible ? '1' : '0' }}"
                                data-has-physical="{{ in_array($product->id, $physicalProductIds) ? '1' : '0' }}"
                                data-has-incoming="{{ in_array($product->id, $incomingProductIds) ? '1' : '0' }}">
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
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="form-check form-switch mb-0" style="padding-left:0;display:flex;align-items:center;gap:6px;">
                                            <input class="form-check-input" type="checkbox" role="switch" id="toggle-warehouse-sale" style="width:32px;height:18px;margin:0;cursor:pointer;flex-shrink:0;">
                                            <label class="form-check-label mb-0" for="toggle-warehouse-sale" style="font-size:11px;font-weight:700;color:#1a4fa0;cursor:pointer;white-space:nowrap;line-height:1;"><i class="bi bi-building-check"></i><span class="d-none d-md-inline ms-1">საწყობშია</span></label>
                                        </div>
                                        <div class="form-check form-switch mb-0" style="padding-left:0;display:flex;align-items:center;gap:6px;">
                                            <input class="form-check-input" type="checkbox" role="switch" id="toggle-transit-sale" style="width:32px;height:18px;margin:0;cursor:pointer;flex-shrink:0;">
                                            <label class="form-check-label mb-0" for="toggle-transit-sale" style="font-size:11px;font-weight:700;color:#a05c00;cursor:pointer;white-space:nowrap;line-height:1;"><i class="bi bi-truck"></i><span class="d-none d-md-inline ms-1">გზაშია</span></label>
                                        </div>
                                    </div>
                                    <button type="button" id="add-sale-line" class="btn btn-sm fw-semibold" style="background:#e8faf0;color:#1a7a4a;border:1.5px solid #b7e4c7;border-radius:20px;font-size:11px;padding:3px 12px;line-height:1;white-space:nowrap;">
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

                                {{-- Gift --}}
                                <div class="mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_gift" value="1" id="chk-is-gift">
                                        <label class="form-check-label" for="chk-is-gift" style="font-size:13px;">
                                            <i class="bi bi-gift me-1" style="color:#e05c2a;"></i>საჩუქარი
                                        </label>
                                    </div>
                                </div>

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
