@extends('layouts.admin.app')

@section('title', translate('Edit brand'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/brand.png') }}" class="w--20" alt="{{ translate('brand') }}">
                </span>
                <span>
                    {{ translate('brand update') }}
                </span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.brand.update', $brand->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('brand name') }}</label>
                                        <input type="text" name="name" value="{{ $brand->name }}"
                                            class="form-control" placeholder="{{ translate('Brand name') }}" maxlength="255"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('description') }}</label>
                                        <textarea name="description" class="form-control" rows="3"
                                            placeholder="{{ translate('Brand description (optional)') }}">{{ $brand->description }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1" {{ $brand->status == 1 ? 'selected' : '' }}>
                                                {{ translate('active') }}</option>
                                            <option value="0" {{ $brand->status == 0 ? 'selected' : '' }}>
                                                {{ translate('inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('featured') }}</label>
                                        <select name="is_featured" class="form-control">
                                            <option value="0" {{ $brand->is_featured == 0 ? 'selected' : '' }}>
                                                {{ translate('no') }}</option>
                                            <option value="1" {{ $brand->is_featured == 1 ? 'selected' : '' }}>
                                                {{ translate('yes') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column justify-content-center h-100">
                                <h5 class="text-center mb-3 text--title text-capitalize">
                                    {{ translate('brand') }} {{ translate('image') }}
                                    <small class="text-danger">*</small>
                                </h5>
                                <label class="upload--vertical">
                                    <input type="file" name="image" id="customFileEg1" class=""
                                        accept=".{{ implode(',.', array_column(IMAGE_EXTENSIONS, 'key')) }}, |image/*"
                                        hidden
                                        data-maxFileSize="{{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}">
                                    <img id="viewer" src="{{ $brand->imageFullPath }}"
                                        alt="{{ translate('brand image') }}" />
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
                                <a href="{{ route('admin.brand.add-new') }}"
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
