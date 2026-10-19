@extends('layouts.admin.app')

@section('title', translate('Edit seller'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/seller.png') }}" class="w--20"
                        alt="{{ translate('seller') }}">
                </span>
                <span>
                    {{ translate('seller update') }}
                </span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.seller.update', $seller->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('seller name') }}</label>
                                        <input type="text" name="name" value="{{ $seller->name }}"
                                            class="form-control" placeholder="{{ translate('Seller name') }}"
                                            maxlength="255" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('email') }}</label>
                                        <input type="email" name="email" value="{{ $seller->email }}"
                                            class="form-control" placeholder="{{ translate('seller@example.com') }}"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('phone') }}</label>
                                        <input type="tel" name="phone" value="{{ $seller->phone }}"
                                            class="form-control" placeholder="{{ translate('+1234567890') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('description') }}</label>
                                        <textarea name="description" class="form-control" rows="2"
                                            placeholder="{{ translate('Seller description (optional)') }}">{{ $seller->description }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('address') }}</label>
                                        <textarea name="address" class="form-control" rows="2"
                                            placeholder="{{ translate('Seller address (optional)') }}">{{ $seller->address }}</textarea>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('city') }}</label>
                                        <input type="text" name="city" value="{{ $seller->city }}"
                                            class="form-control" placeholder="{{ translate('City') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('state') }}</label>
                                        <input type="text" name="state" value="{{ $seller->state }}"
                                            class="form-control" placeholder="{{ translate('State') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('country') }}</label>
                                        <input type="text" name="country" value="{{ $seller->country }}"
                                            class="form-control" placeholder="{{ translate('Country') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('zip code') }}</label>
                                        <input type="text" name="zip_code" value="{{ $seller->zip_code }}"
                                            class="form-control" placeholder="{{ translate('Zip code') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('website') }}</label>
                                        <input type="url" name="website" value="{{ $seller->website }}"
                                            class="form-control" placeholder="{{ translate('https://example.com') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1" {{ $seller->status == 1 ? 'selected' : '' }}>
                                                {{ translate('active') }}</option>
                                            <option value="0" {{ $seller->status == 0 ? 'selected' : '' }}>
                                                {{ translate('inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('featured') }}</label>
                                        <select name="is_featured" class="form-control">
                                            <option value="0" {{ $seller->is_featured == 0 ? 'selected' : '' }}>
                                                {{ translate('no') }}</option>
                                            <option value="1" {{ $seller->is_featured == 1 ? 'selected' : '' }}>
                                                {{ translate('yes') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('commission rate') }} (%)</label>
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="commission_rate" value="{{ $seller->commission_rate }}"
                                            class="form-control" placeholder="{{ translate('Commission rate') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column justify-content-center h-100">
                                <h5 class="text-center mb-3 text--title text-capitalize">
                                    {{ translate('seller') }} {{ translate('logo') }}
                                    <small class="text-danger">*</small>
                                </h5>
                                <label class="upload--vertical">
                                    <input type="file" name="logo" id="customFileEg1" class=""
                                        accept=".{{ implode(',.', array_column(IMAGE_EXTENSIONS, 'key')) }}, |image/*"
                                        hidden
                                        data-maxFileSize="{{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}">
                                    <img id="viewer" src="{{ $seller->logoFullPath }}"
                                        alt="{{ translate('seller logo') }}" />
                                </label>
                                <p class="fs-10 m-0 text-center mt-3">
                                    {{ implode(', ', array_column(IMAGE_EXTENSIONS, 'key')) }} : Max
                                    {{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}
                                    <span class="text-dark font-semibold">(1:1)</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="btn--container justify-content-end">
                                <a href="{{ route('admin.seller.add-new') }}"
                                    class="btn btn--reset">{{ translate('back') }}</a>
                                <button type="submit" class="btn btn--primary">{{ translate('update') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        // Preview image before upload
        document.getElementById('customFileEg1').onchange = function(evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('viewer').src = URL.createObjectURL(file);
            }
        };

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        @endif
    </script>
@endpush
