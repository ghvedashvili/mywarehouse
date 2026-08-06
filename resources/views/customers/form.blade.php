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
                                        <i class="fa fa-envelope text-warning me-1"></i> Email მისამართი
                                    </label>
                                    <input type="email" class="form-control form-control-lg fs-6 border-light-subtle bg-light"
                                           id="email" name="email" placeholder="example@mail.com (არასავალდებულო)">
                                    <div class="invalid-feedback small">ჩაწერეთ ვალიდური Email</div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-600 text-muted small mb-1">ტელეფონი *</label>
                                        <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light"
                                               id="tel" name="tel" required placeholder="5xx xx xx xx" inputmode="numeric"
                                               oninput="this.value=this.value.replace(/[^\d\s]/g,'')">
                                        <div class="invalid-feedback small">აუცილებელია</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-600 text-muted small mb-1">ალტ. ტელ.</label>
                                        <input type="text" class="form-control form-control-lg fs-6 border-light-subtle bg-light"
                                               id="alternative_tel" name="alternative_tel" placeholder="(სურვილისამებრ)" inputmode="numeric"
                                               oninput="this.value=this.value.replace(/[^\d\s]/g,'')">
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
                    
                    <div class="text-muted small mt-3 ps-2">* ნიშნით აღნიშნული ველები სავალდებულოა. Email არასავალდებულოა.</div>
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
    .fw-600 { font-weight: 600; }

    @media (max-width: 767px) {
        #modal-form .modal-dialog {
            margin: 8px;
            max-width: calc(100vw - 16px) !important;
            height: calc(100vh - 16px);
        }
        #modal-form .modal-content {
            height: 100%;
            overflow: hidden;
        }
        #modal-form #form-item {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }
        #modal-form .modal-header {
            flex-shrink: 0;
            padding: 12px 14px !important;
        }
        #modal-form .modal-footer {
            flex-shrink: 0;
            padding: 10px 14px !important;
        }
        #modal-form .modal-body {
            flex: 1 1 0;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 12px 14px !important;
        }
        #modal-form .modal-body .bg-white { padding: 10px !important; }
        #modal-form .form-control-lg,
        #modal-form .form-select-lg { font-size: 14px !important; padding: 8px 10px !important; }
        #modal-form .btn-lg { font-size: 14px !important; padding: 8px 18px !important; }
    }
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        height: calc(1.5em + 1rem + 2px);
        padding: .375rem .75rem;
        font-size: 1rem;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        background-color: #f8f9fa;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        color: #212529;
        padding: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        top: 0;
    }
    .select2-dropdown { border-color: #dee2e6; border-radius: .375rem; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('modal-form');
        if (!el) return;
        el.addEventListener('hidden.bs.modal', function () {
            if (document.getElementById('modal-sale') &&
                (typeof save_method === 'undefined' || save_method === 'add')) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-sale')).show();
            }
        });
    });
</script>