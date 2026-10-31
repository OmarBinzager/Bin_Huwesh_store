<div class="modal fade add-customer" id="add-customer">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Add new customer') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{route('admin.pos.customer.store')}}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('First name') }} <span class="input-label-secondary text-danger">*</span></label>
                                <input type="text" name="f_name" class="form-control" value="" placeholder="{{ translate('First name') }}" required="">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Last name') }} <span class="input-label-secondary text-danger">*</span></label>
                                <input type="text" name="l_name" class="form-control" value="" placeholder="{{ translate('Last name') }}" required="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Phone (With country code)') }} <span class="input-label-secondary text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="" placeholder="{{ translate('Phone') }}" required="">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Email') }} <span class="input-label-secondary">({{ translate('optional') }})</span></label>
                                <input type="email" name="email" class="form-control" value="" placeholder="{{ translate('Ex : ex@example.com') }}">
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-2">
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="tio-poi"></i> {{ translate('Customer Address (Optional)') }}
                        </h6>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('address') }}</label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="{{ translate('Enter detailed address, street, landmark') }}"></textarea>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Road') }}</label>
                                    <input type="text" name="road" class="form-control" placeholder="{{ translate('Road / Street') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('House') }}</label>
                                    <input type="text" name="house" class="form-control" placeholder="{{ translate('House / Building No') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Floor') }}</label>
                                    <input type="text" name="floor" class="form-control" placeholder="{{ translate('Floor / Apt') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn--container justify-content-end">
                        <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                        <button type="submit" id="submit_new_customer" class="btn btn--primary">{{ translate('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
