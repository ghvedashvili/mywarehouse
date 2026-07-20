<style>
#purchase-lines-table th, #purchase-lines-table td { vertical-align: middle; }
.line-price-geo { color: #00a65a; font-weight: 700; }
.line-fifo      { font-size: 10px; color: #8e44ad; display: block; margin-top: 2px; }

@media (max-width: 767px) {
    #modal-purchase .table-responsive { overflow: visible !important; }
    #purchase-lines-table { min-width: 0 !important; }
    #purchase-lines-table thead { display: none !important; }
    #purchase-lines-table, #purchase-lines-table tbody { display: block !important; width: 100% !important; }
    #purchase-lines-table tr.purchase-line {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        padding: 10px;
        background: #fff;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 1px 6px rgba(0,0,0,.07);
    }
    #purchase-lines-table tr.purchase-line td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
    }
    /* Product select — full width */
    #purchase-lines-table tr.purchase-line td:first-child { grid-column: 1 / -1; }
    /* Remove button — full width, right-aligned */
    #purchase-lines-table tr.purchase-line td:last-child { grid-column: 1 / -1; text-align: right; }
    #purchase-lines-table .select2-container { width: 100% !important; }
    #purchase-lines-table .line-size,
    #purchase-lines-table .line-qty,
    #purchase-lines-table .line-price-usa,
    #purchase-lines-table .line-transport,
    #purchase-lines-table .line-price-geo { width: 100% !important; font-size: 16px !important; }
}
</style>

<div class="modal fade" id="modal-purchase" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold" id="purchase-modal-title">📦 ახალი შესყიდვა</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3">
                <form id="form-purchase" method="post">
                    @csrf @method('POST')
                    <input type="hidden" name="id"         id="purchase_id">
                    <input type="hidden" name="order_type" value="purchase">
                    <input type="hidden" name="status_id"  value="2">

                    {{-- ─── Product Lines ─── --}}
                    <div class="table-responsive mb-2">
                        <table class="table table-sm table-bordered align-middle mb-0" id="purchase-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th>პროდუქტი</th>
                                    <th style="width:115px">ზომა</th>
                                    <th style="width:75px">რაოდ.</th>
                                    <th style="width:110px">ფასი ($)</th>
                                    <th style="width:110px">ტრანსპ. ($)</th>
                                    <th style="width:115px">Price (₾)</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-lines-body"></tbody>
                        </table>
                    </div>

                    <button type="button" id="btn-add-line" class="btn btn-outline-success btn-sm mb-3"
                            onclick="addPurchaseLine()">
                        <i class="fa fa-plus me-1"></i> პროდუქტის დამატება
                    </button>

                    {{-- ─── Courier section (return/exchange purchase edit only) ─── --}}
                    <div id="purchase_courier_section" style="display:none;" class="p-2 border rounded bg-light mb-3">
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

                    {{-- ─── Comment ─── --}}
                    <div>
                        <label class="form-label fw-semibold">შენიშვნა</label>
                        <textarea name="comment" id="purchase_comment"
                                  class="form-control form-control-sm" rows="3" placeholder="შენიშვნა..."></textarea>
                    </div>

                </form>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                <button type="submit" form="form-purchase" id="btn-purchase-save" class="btn btn-success">
                    <i class="fa fa-save me-1"></i> შენახვა
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Product options template (server-rendered, cloned per row in JS) --}}
<template id="tpl-product-options">
    <option value="">— პროდუქტი —</option>
    @foreach($products as $p)
        <option value="{{ $p->id }}"
                data-price-ge="{{ $p->price_geo }}"
                data-sizes="{{ $p->sizes ?? '' }}"
                data-image="{{ $p->image_url ?? '' }}"
                data-divisible="{{ $p->category?->is_divisible ? '1' : '0' }}">
            {{ $p->name }}@if($p->product_code) ({{ $p->product_code }})@endif
        </option>
    @endforeach
</template>
