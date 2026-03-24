<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 10px; overflow: hidden;">
            <form id="form-item" method="post" class="form-horizontal" data-toggle="validator">
                {{ csrf_field() }} {{ method_field('POST') }}

                <div class="modal-header bg-gray-light" style="border-bottom: 1px solid #eee;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="font-weight: bold; color: #333;"></h4>
                </div>

                <div class="modal-body" style="padding: 25px 30px;">
                    <input type="hidden" id="id" name="id">

                    <div class="row">
                        <div class="col-md-6" style="border-right: 1px solid #f0f0f0;">
                            <div class="form-group mb-3" style="padding: 0 15px;">
                                <label style="font-weight: 600;"><i class="fa fa-user text-blue"></i> Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" autofocus required 
                                       style="border-radius: 4px;" placeholder="Enter customer name">
                                <span class="help-block with-errors"></span>
                            </div>

                            <div class="form-group mb-3" style="padding: 0 15px;">
                                <label style="font-weight: 600;"><i class="fa fa-map-marker text-red"></i> City</label>
                                <select name="city_id" id="city_id" class="form-control" required style="border-radius: 4px;">
                                    <option value="">-- Choose City --</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                <span class="help-block with-errors"></span>
                            </div>

                            <div class="form-group mb-3" style="padding: 0 15px;">
                                <label style="font-weight: 600;"><i class="fa fa-home text-green"></i> Exact Address</label>
                                <input type="text" class="form-control" id="address" name="address" required 
                                       style="border-radius: 4px;" placeholder="Street, building, apt...">
                                <span class="help-block with-errors"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3" style="padding: 0 15px;">
                                <label style="font-weight: 600;"><i class="fa fa-envelope text-orange"></i> Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       style="border-radius: 4px;" placeholder="example@mail.com">
                                <span class="help-block with-errors"></span>
                            </div>

                            <div class="row" style="padding: 0 15px;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="font-weight: 600;">Main Tel</label>
                                        <input type="text" class="form-control" id="tel" name="tel" required 
                                               style="border-radius: 4px;" placeholder="5xx xx xx xx">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label style="font-weight: 600;">Alt Tel</label>
                                        <input type="text" class="form-control" id="alternative_tel" name="alternative_tel" 
                                               style="border-radius: 4px;" placeholder="Optional">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="padding: 0 15px;">
                                <label style="font-weight: 600;"><i class="fa fa-commenting-o"></i> Comment / Note</label>
                                <textarea class="form-control" id="comment" name="comment" rows="2" 
                                          style="border-radius: 4px; resize: vertical;" placeholder="Additional info..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-gray-light" style="border-radius: 0 0 10px 10px; border-top: 1px solid #eee;">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-flat" style="padding: 7px 35px; font-weight: bold;">
                        <i class="fa fa-save"></i> Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $('#modal-form').on('hidden.bs.modal', function () {
    if ($('#modal-sale').length && (save_method === 'add' || save_method === undefined)) {
        $('#modal-sale').modal('show');
    }
});
</script>