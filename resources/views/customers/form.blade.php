<div class="modal fade" id="modal-form" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            
            <form id="form-item" method="post" novalidate>
                {{ csrf_field() }} {{ method_field('POST') }}

                <div class="modal-header bg-white border-bottom-0 shadow-sm p-3">
                    <h5 class="modal-title fw-bold text-dark" id="modal-title">
                        </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <input type="hidden" id="id" name="id">

                    <div class="row g-3">
                        
                        <div class="col-12 col-md-6">
                            <div class="bg-white p-3 rounded-3 shadow-sm border">
                                <div class="mb-3">
                                    <label class="form-label fw-600 text-muted small mb-1">
                                        <i class="fa fa-user text-primary me-1"></i> სრული სახელი *
                                    </label>
                                    <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light" 
                                           id="name" name="name" required placeholder="მაგ: გიორგი კვარაცხელია">
                                    <div class="invalid-feedback small">გთხოვთ მიუთითოთ სახელი</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-600 text-muted small mb-1">
                                        <i class="fa fa-map-marker text-danger me-1"></i> ქალაქი *
                                    </label>
                                    <select name="city_id" id="city_id" class="form-select form-select-lg fs-6 border-light-subtle bg-light" required>
                                        <option value="">-- აირჩიეთ --</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback small">აირჩიეთ ქალაქი</div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-600 text-muted small mb-1">
                                        <i class="fa fa-home text-success me-1"></i> ზუსტი მისამართი *
                                    </label>
                                    <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light" 
                                           id="address" name="address" required placeholder="ქუჩა, კორპუსი, ბინა...">
                                    <div class="invalid-feedback small">მიუთითეთ მისამართი</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="bg-white p-3 rounded-3 shadow-sm border">
                                <div class="mb-3">
                                    <label class="form-label fw-600 text-muted small mb-1">
                                        <i class="fa fa-envelope text-warning me-1"></i> Email მისამართი *
                                    </label>
                                    <input type="email" class="form-control form-control-lg fs-6 border-light-subtle bg-light" 
                                           id="email" name="email" required placeholder="example@mail.com">
                                    <div class="invalid-feedback small">ჩაწერეთ ვალიდური Email</div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-600 text-muted small mb-1">ტელეფონი *</label>
                                        <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light" 
                                               id="tel" name="tel" required placeholder="5xx xx xx xx">
                                        <div class="invalid-feedback small">აუცილებელია</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-600 text-muted small mb-1">ალტ. ტელ.</label>
                                        <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light" 
                                               id="alternative_tel" name="alternative_tel" placeholder="(სურვილისამებრ)">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-600 text-muted small mb-1">
                                        <i class="fa fa-commenting-o me-1"></i> შენიშვნა / კომენტარი
                                    </label>
                                    <textarea class="form-control fs-6 border-light-subtle bg-light resize-none" 
                                              id="comment" name="comment" rows="3" placeholder="დამატებითი ინფორმაცია..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-muted small mt-3 ps-2">* ნიშნით აღნიშნული ველები სავალდებულოა</div>
                </div>

                <div class="modal-footer bg-white border-top-0 p-3 justify-content-center justify-content-md-end">
                    <button type="button" class="btn btn-light btn-lg fs-6 px-4 rounded-pill shadow-sm me-2" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> გაუქმება
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg fs-6 px-5 rounded-pill shadow fw-bold">
                        <i class="fa fa-save me-1"></i> შენახვა
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .resize-none { resize: none; }
    /* Bootstrap-ს არ აქვს fw-600 კლასი, ამიტომ inline სტილის ნაცვლად აქ დავწეროთ */
    .fw-600 { font-weight: 600; }
</style>
<script>
    $('#modal-form').on('hidden.bs.modal', function () {
    if ($('#modal-sale').length && (save_method === 'add' || save_method === undefined)) {
        $('#modal-sale').modal('show');
    }
});
</script>