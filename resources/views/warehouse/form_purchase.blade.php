<div class="modal fade" id="modal-purchase" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 10px;">
            <form id="form-purchase" method="post" class="form-horizontal">
                {{ csrf_field() }} {{ method_field('POST') }}

                <input type="hidden" name="id"            id="purchase_id">
                <input type="hidden" name="order_type"    value="purchase">
                <input type="hidden" name="price_usa"     id="purchase_price_usa_hidden">
                <input type="hidden" name="courier_price_international" id="purchase_transport_hidden" value="0">

                <div class="modal-header bg-gray-light">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="purchase-modal-title" style="font-weight: bold;">
                        📦 ახალი შესყიდვა
                    </h4>
                </div>

                <div class="modal-body" style="padding: 20px 25px;">
                    <div class="row">

                        {{-- ════ LEFT ════ --}}
                        <div class="col-md-8">

                            {{-- 1. PRODUCT + SIZE + PRICES --}}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="font-weight:600;">1. პროდუქტი</label>
                                        <select name="product_id" id="purchase_product_id"
                                                class="form-control select2-purchase" style="width:100%;" required>
                                            <option value="">— აირჩიე პროდუქტი —</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-price-ge="{{ $product->price_geo }}"
                                                    data-sizes="{{ $product->sizes }}"
                                                    data-image="{{ asset(ltrim($product->image ?? '', '/')) }}">
                                                    {{ $product->name }}
                                                    @if($product->product_code)({{ $product->product_code }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="font-weight:600;">ზომა</label>
                                        <select name="product_size" id="purchase_size" class="form-control" required>
                                            <option value="">ზომა</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="font-weight:600;">Price (₾)</label>
                                        <input type="number" id="purchase_price_geo_input"
                                               name="price_georgia"
                                               class="form-control" step="0.01" min="0" placeholder="0.00"
                                               style="font-weight:bold; color:#00a65a;">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="font-weight:600;">პროდუქტის ფასი ($)</label>
                                        <input type="number" id="purchase_price_usa_input"
                                               class="form-control" step="0.01" min="0" placeholder="0.00"
                                               style="font-weight:bold; color:#357ca5;">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label style="font-weight:600;">ტრანსპ. ($)</label>
                                        <input type="number" id="purchase_transport_input"
                                               class="form-control" step="0.01" min="0" placeholder="0.00"
                                               style="font-weight:bold; color:#8e44ad;">
                                    </div>
                                </div>
                            </div>

                            {{-- COST PRICE BANNER --}}
                            <div class="row" style="margin-top:-5px; margin-bottom:8px;">
                                <div class="col-md-12">
                                    <div style="background:#fff8e1; border:1px solid #ffe082; border-radius:6px;
                                                padding:8px 14px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                                        <div>
                                            <span style="font-size:11px; color:#888;">🧮 ამ შესყიდვის თვითღირებულება (FIFO):</span>
                                            <strong id="purchase_cost_price_display"
                                                    style="font-size:15px; color:#e67e22; margin-left:4px;">$0.00</strong>
                                        </div>
                                        <div id="fifo_current_block" style="display:none;">
                                            <span style="font-size:11px; color:#888;">📊 მიმდინარე FIFO:</span>
                                            <strong id="fifo_current_display"
                                                    style="font-size:15px; color:#27ae60; margin-left:4px;">$0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. QTY + DISCOUNT + STATUS --}}
                            <div class="row" style="margin-top:5px;">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="font-weight:600;">რაოდენობა</label>
                                        <input type="number" name="quantity" id="purchase_qty"
                                               class="form-control" min="1" value="1" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label style="font-weight:600;">Discount ($)</label>
                                        <input type="number" name="discount" id="purchase_discount"
                                               class="form-control" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label style="font-weight:600;">სტატუსი</label>
                                        <select name="status_id" id="purchase_status_id" class="form-control">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. BANKS --}}
                            <div class="well well-sm" style="background:#f4f4f4; border:1px solid #ddd; padding:10px; margin-top:5px;">
                                <label style="font-weight:600; display:block; margin-bottom:6px;">3. გადახდა</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-addon">TBC</span>
                                    <input type="number" name="paid_tbc" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-addon">BOG</span>
                                    <input type="number" name="paid_bog" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-addon">Lib</span>
                                    <input type="number" name="paid_lib" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                    <span class="input-group-addon">Cash</span>
                                    <input type="number" name="paid_cash" class="form-control purchase-payment" placeholder="0" step="0.01" value="0">
                                </div>
                                <div style="margin-top:10px; min-height:40px;">
                                    <small style="display:block; color:#777;">Summary:</small>
                                    <strong id="purchase_summary_text" style="font-size:13px;">შეიყვანეთ მონაცემები</strong>
                                </div>
                            </div>

                            {{-- 4. COMMENT --}}
                            <div class="form-group" style="margin-top:10px;">
                                <label style="font-weight:600;">4. შენიშვნა</label>
                                <textarea name="comment" id="purchase_comment" class="form-control"
                                          rows="2" placeholder="შენიშვნა..."></textarea>
                            </div>

                        </div>

                        {{-- ════ RIGHT: IMAGE + STOCK ════ --}}
                        <div class="col-md-4 text-center" style="border-left:1px solid #eee;">
                            <label style="font-weight:bold; display:block; margin-bottom:10px;">Preview</label>

                            <div style="width:100%; height:160px; border:2px dashed #ddd; border-radius:10px;
                                        display:flex; align-items:center; justify-content:center;
                                        background:#fff; overflow:hidden; margin-bottom:15px;">
                                <img id="purchase_preview" class="img-responsive" style="display:none; max-height:155px;">
                                <span id="purchase_no_img" class="text-muted">No Image</span>
                            </div>

                            <div id="current-stock-info"
                                 style="display:none; background:#f4f4f4; border-radius:8px; padding:12px; text-align:left;">
                                <div style="font-size:11px; font-weight:700; text-transform:uppercase;
                                            color:#888; margin-bottom:8px; border-bottom:1px solid #ddd; padding-bottom:4px;">
                                    მიმდინარე ნაშთი
                                </div>
                                <div class="row text-center">
                                    <div class="col-xs-4">
                                        <div style="font-size:22px; font-weight:800; color:#3c763d;" id="si-physical">0</div>
                                        <div style="font-size:10px; color:#888;">📦 ფიზიკური</div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div style="font-size:22px; font-weight:800; color:#31708f;" id="si-incoming">0</div>
                                        <div style="font-size:10px; color:#888;">🚚 გზაში</div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div style="font-size:22px; font-weight:800; color:#8a6d3b;" id="si-reserved">0</div>
                                        <div style="font-size:10px; color:#888;">🔒 დაჯავშნ.</div>
                                    </div>
                                </div>
                                <div style="margin-top:10px; border-top:1px solid #ddd; padding-top:8px; text-align:center;">
                                    <span style="font-size:11px; color:#888;">მიმდინარე FIFO თვითღ.:</span>
                                    <strong id="si-fifo-cost" style="color:#8e44ad; font-size:13px;">—</strong>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">გაუქმება</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> შენახვა
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>