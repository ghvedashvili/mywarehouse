<div class="modal fade" id="modal-purchase" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:10px;">
            <form id="form-purchase" method="post">
                @csrf @method('POST')
                <input type="hidden" name="id" id="purchase_id">
                <input type="hidden" name="order_type" value="purchase">
                <input type="hidden" name="price_usa" id="purchase_price_usa_hidden">
                <input type="hidden" name="courier_price_international" id="purchase_transport_hidden" value="0">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="purchase-modal-title">📦 ახალი შესყიდვა</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">პროდუქტი</label>
                                    <select name="product_id" id="purchase_product_id" class="form-select select2-purchase" required>
                                        <option value="">— აირჩიე —</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price-ge="{{ $product->price_geo }}" data-sizes="{{ $product->sizes }}" data-image="{{ asset(ltrim($product->image ?? '', '/')) }}">
                                                {{ $product->name }}@if($product->product_code) ({{ $product->product_code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ზომა</label>
                                    <select name="product_size" id="purchase_size" class="form-select" required><option value="">ზომა</option></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Price (₾)</label>
                                    <input type="number" id="purchase_price_geo_input" name="price_georgia" class="form-control fw-bold text-success bg-light" step="0.01" min="0" placeholder="0.00" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ფასი ($)</label>
                                    <input type="number" id="purchase_price_usa_input" class="form-control fw-bold" step="0.01" min="0" placeholder="0.00" style="color:#357ca5;">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ტრანსპ. ($)</label>
                                    <input type="number" id="purchase_transport_input" class="form-control fw-bold" step="0.01" min="0" placeholder="0.00" style="color:#8e44ad;">
                                </div>
                            </div>

                            <div class="p-2 rounded border mb-3" style="background:#fff8e1; border-color:#ffe082!important;">
                                <span class="text-muted small">🧮 თვითღირებულება (FIFO):</span>
                                <strong id="purchase_cost_price_display" style="color:#e67e22; font-size:15px;" class="ms-1">$0.00</strong>
                                <span id="fifo_current_block" style="display:none;" class="ms-3">
                                    <span class="text-muted small">📊 მიმდინარე FIFO:</span>
                                    <strong id="fifo_current_display" class="text-success ms-1">$0.00</strong>
                                </span>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">რაოდენობა</label>
                                    <input type="number" name="quantity" id="purchase_qty" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Discount ($)</label>
                                    <input type="number" name="discount" id="purchase_discount" class="form-control" step="0.01" min="0" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">სტატუსი</label>
                                    <select name="status_id" id="purchase_status_id" class="form-select">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="p-3 border rounded bg-light mb-2">
                                <label class="form-label fw-semibold">გადახდა</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">TBC</span>
                                    <input type="number" name="paid_tbc" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-text">BOG</span>
                                    <input type="number" name="paid_bog" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-text">Lib</span>
                                    <input type="number" name="paid_lib" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-text">Cash</span>
                                    <input type="number" name="paid_cash" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Summary:</small>
                                    <strong id="purchase_summary_text" style="font-size:13px;">შეიყვანეთ მონაცემები</strong>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">შენიშვნა</label>
                                <textarea name="comment" id="purchase_comment" class="form-control" rows="2" placeholder="შენიშვნა..."></textarea>
                            </div>
                        </div>

                        <div class="col-md-4 text-center border-start">
                            <label class="form-label fw-bold d-block">Preview</label>
                            <div class="d-flex align-items-center justify-content-center border rounded mb-3" style="height:160px; border-style:dashed!important; overflow:hidden;">
                                <img id="purchase_preview" class="img-fluid" style="display:none; max-height:155px;">
                                <span id="purchase_no_img" class="text-muted">No Image</span>
                            </div>
                            <div id="current-stock-info" style="display:none;" class="p-3 border rounded bg-light text-start">
                                <div class="fw-bold text-uppercase text-muted mb-2" style="font-size:10px; letter-spacing:0.5px; border-bottom:1px solid #ddd; padding-bottom:4px;">მიმდინარე ნაშთი</div>
                                <div class="row text-center g-0">
                                    <div class="col-4"><div class="fw-bold text-success" style="font-size:22px;" id="si-physical">0</div><div class="text-muted" style="font-size:10px;">📦 ფიზ.</div></div>
                                    <div class="col-4"><div class="fw-bold" style="font-size:22px; color:#31708f;" id="si-incoming">0</div><div class="text-muted" style="font-size:10px;">🚚 გზაში</div></div>
                                    <div class="col-4"><div class="fw-bold" style="font-size:22px; color:#8a6d3b;" id="si-reserved">0</div><div class="text-muted" style="font-size:10px;">🔒 დაჯავშნ.</div></div>
                                </div>
                                <div class="mt-2 pt-2 border-top text-center">
                                    <span class="text-muted" style="font-size:11px;">FIFO:</span>
                                    <strong id="si-fifo-cost" style="color:#8e44ad;">—</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i> შენახვა</button>
                </div>
            </form>
        </div>
    </div>
</div>