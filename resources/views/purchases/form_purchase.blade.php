<style>
.purchase-preview-box {
    height: 80px;
    border-style: dashed !important;
    overflow: hidden;
}
@media (min-width: 768px) {
    .purchase-preview-box { height: 120px; }
}
</style>

<div class="modal fade" id="modal-purchase" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            {{-- ── Header — direct child of modal-content ── --}}
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold" id="purchase-modal-title">📦 ახალი შესყიდვა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- ── Body — scrollable, form inside ── --}}
            <div class="modal-body p-3">
                <form id="form-purchase" method="post">
                    @csrf @method('POST')
                    <input type="hidden" name="id"                          id="purchase_id">
                    <input type="hidden" name="order_type"                  value="purchase">
                    <input type="hidden" name="price_usa"                   id="purchase_price_usa_hidden">
                    <input type="hidden" name="courier_price_international"  id="purchase_transport_hidden" value="0">

                    <div class="row g-2">

                        {{-- ═══ LEFT: ძირითადი ველები ═══ --}}
                        <div class="col-12 col-md-8">

                            {{-- პროდუქტი + ზომა + ფასები --}}
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label class="form-label fw-semibold">პროდუქტი</label>
                                    <select name="product_id" id="purchase_product_id" class="form-select select2-purchase" required>
                                        <option value="">— აირჩიე —</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                    data-price-ge="{{ $product->price_geo }}"
                                                    data-sizes="{{ $product->sizes }}"
                                                    data-image="{{ asset(ltrim($product->image ?? '', '/')) }}">
                                                {{ $product->name }}@if($product->product_code) ({{ $product->product_code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="form-label fw-semibold">ზომა</label>
                                    <select name="product_size" id="purchase_size" class="form-select" required>
                                        <option value="">ზომა</option>
                                    </select>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="form-label fw-semibold">Price (₾)</label>
                                    <input type="number" id="purchase_price_geo_input" name="price_georgia"
                                           class="form-control fw-bold text-success bg-light"
                                           step="0.01" min="0" placeholder="0.00" readonly>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="form-label fw-semibold">ფასი ($)</label>
                                    <input type="number" id="purchase_price_usa_input"
                                           class="form-control fw-bold" style="color:#357ca5;"
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-6 col-sm-3 col-md-2" id="purchase_transport_wrap">
                                    <label class="form-label fw-semibold">ტრანსპ. ($)</label>
                                    <input type="number" id="purchase_transport_input"
                                           class="form-control fw-bold" style="color:#8e44ad;"
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>

                            {{-- საკურიეო (return/exchange purchase-ისთვის) --}}
                            <div id="purchase_courier_section" style="display:none;" class="p-2 border rounded bg-light mb-2">
                                <div class="fw-semibold mb-2" style="font-size:13px;">საკურიეო (₾)</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_courier_type" id="pc_none" value="none" checked>
                                        <label class="form-check-label" for="pc_none">არ არის</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_courier_type" id="pc_tbilisi" value="tbilisi">
                                        <label class="form-check-label" for="pc_tbilisi">თბილისი</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_courier_type" id="pc_region" value="region">
                                        <label class="form-check-label" for="pc_region">რეგიონი</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_courier_type" id="pc_village" value="village">
                                        <label class="form-check-label" for="pc_village">სოფელი</label>
                                    </div>
                                </div>
                            </div>

                            {{-- FIFO ბარი --}}
                            <div class="d-flex align-items-center flex-wrap gap-2 p-2 rounded border mb-2"
                                 style="background:#fff8e1; border-color:#ffe082!important; font-size:13px;">
                                <span class="text-muted small">🧮 FIFO:</span>
                                <strong id="purchase_cost_price_display" style="color:#e67e22;">$0.00</strong>
                                <span id="fifo_current_block" style="display:none;" class="text-muted small">
                                    | 📊 მიმდ.: <strong id="fifo_current_display" class="text-success">$0.00</strong>
                                </span>
                            </div>

                            {{-- რაოდ + discount + სტატუსი --}}
                            <div class="row g-2 mb-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold">რაოდენობა</label>
                                    <input type="number" name="quantity" id="purchase_qty"
                                           class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fw-semibold">Discount ($)</label>
                                    <input type="number" name="discount" id="purchase_discount"
                                           class="form-control" step="0.01" min="0" value="0">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">სტატუსი</label>
                                    <select name="status_id" id="purchase_status_id" class="form-select">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- გადახდა --}}
                            <div class="p-2 border rounded bg-light mb-2">
                                <div class="fw-semibold mb-2" style="font-size:13px;">გადახდა ($)</div>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label form-label-sm text-muted mb-1">TBC</label>
                                        <input type="number" name="paid_tbc" class="form-control form-control-sm purchase-payment" placeholder="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label form-label-sm text-muted mb-1">BOG</label>
                                        <input type="number" name="paid_bog" class="form-control form-control-sm purchase-payment" placeholder="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label form-label-sm text-muted mb-1">Lib</label>
                                        <input type="number" name="paid_lib" class="form-control form-control-sm purchase-payment" placeholder="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label form-label-sm text-muted mb-1">Cash</label>
                                        <input type="number" name="paid_cash" class="form-control form-control-sm purchase-payment" placeholder="0" step="0.01" value="0">
                                    </div>
                                </div>
                                <div class="mt-1 pt-1 border-top">
                                    <small class="text-muted">Summary: </small>
                                    <strong id="purchase_summary_text" style="font-size:13px;">შეიყვანეთ მონაცემები</strong>
                                </div>
                            </div>

                            {{-- შენიშვნა --}}
                            <div>
                                <label class="form-label fw-semibold">შენიშვნა</label>
                                <textarea name="comment" id="purchase_comment"
                                          class="form-control form-control-sm" rows="2" placeholder="შენიშვნა..."></textarea>
                            </div>
                        </div>

                        {{-- ═══ RIGHT: Preview + ნაშთი ═══ --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold d-block text-center" style="font-size:12px;">Preview</label>
                            <div class="d-flex align-items-center justify-content-center border rounded mb-2 purchase-preview-box">
                                <img id="purchase_preview" class="img-fluid" style="display:none; max-height:100%;">
                                <span id="purchase_no_img" class="text-muted small">No Image</span>
                            </div>
                            <div id="current-stock-info" style="display:none;" class="p-2 border rounded bg-light">
                                <div class="fw-bold text-uppercase text-muted mb-1"
                                     style="font-size:10px; letter-spacing:0.5px; border-bottom:1px solid #ddd; padding-bottom:3px;">
                                    მიმდინარე ნაშთი
                                </div>
                                <div class="row text-center g-0">
                                    <div class="col-4">
                                        <div class="fw-bold text-success" style="font-size:18px;" id="si-physical">0</div>
                                        <div class="text-muted" style="font-size:10px;">📦 ფიზ.</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold" style="font-size:18px; color:#31708f;" id="si-incoming">0</div>
                                        <div class="text-muted" style="font-size:10px;">🚚 გზაში</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold" style="font-size:18px; color:#8a6d3b;" id="si-reserved">0</div>
                                        <div class="text-muted" style="font-size:10px;">🔒 დაჯავშნ.</div>
                                    </div>
                                </div>
                                <div class="mt-1 pt-1 border-top text-center">
                                    <span class="text-muted" style="font-size:11px;">FIFO:</span>
                                    <strong id="si-fifo-cost" style="color:#8e44ad;">—</strong>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /row --}}
                </form>
            </div>{{-- /modal-body --}}

            {{-- ── Footer — direct child → ყოველთვის ჩანს ── --}}
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="submit" form="form-purchase" class="btn btn-success">
                    <i class="fa fa-save me-1"></i> შენახვა
                </button>
            </div>

        </div>
    </div>
</div>
