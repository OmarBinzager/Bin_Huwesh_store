@extends('layouts.admin.app')

@section('title', translate('Add new brand'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/brand.png') }}" class="w--20" alt="{{ translate('brand') }}">
                </span>
                <span>
                    {{ translate('brand setup') }}
                </span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.brand.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('brand name') }}</label>
                                        <input type="text" name="name" value="{{ old('name') }}"
                                            class="form-control" placeholder="{{ translate('New brand') }}" maxlength="255"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('description') }}</label>
                                        <textarea name="description" class="form-control" rows="3"
                                            placeholder="{{ translate('Brand description (optional)') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1">{{ translate('active') }}</option>
                                            <option value="0">{{ translate('inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('featured') }}</label>
                                        <select name="is_featured" class="form-control">
                                            <option value="0">{{ translate('no') }}</option>
                                            <option value="1">{{ translate('yes') }}</option>
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
                                    <img id="viewer" src="{{ asset('public/assets/admin/img/upload-vertical.png') }}"
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
                                <button type="reset" class="btn btn--reset">{{ translate('reset') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card--header order-top">
                <div class="d-flex gap-2 align-items-center">
                    <h5 class="mb-0"> {{ translate('Brand List') }}
                        <span class="badge badge-soft-dark rounded-pill fs-10 ml-1">{{ $brands->total() }}</span>
                    </h5>
                </div>

                <div class="d-flex flex-sm-nowrap flex-wrap gap-sm-3 gap-3">
                    <form action="{{ request()->url() }}" method="GET">
                        @foreach (request()->except('search', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h-30"
                                placeholder="{{ translate('Search by ID or Name') }}" aria-label="Search"
                                value="{{ $search }}" autocomplete="off">

                            <div class="input-group-append h-30">
                                <button type="submit" class="input-group-text title-bg3 p-2 text-white">
                                    <i class="tio-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive datatable-custom">
                <table
                    class="table table-hover table-border table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('#') }}</th>
                            <th class="border-0">{{ translate('brand image') }}</th>
                            <th class="border-0">{{ translate('brand name') }}</th>
                            <th class="border-0">{{ translate('slug') }}</th>
                            <th class="text-center border-0">{{ translate('featured') }}</th>
                            <th class="text-center border-0">{{ translate('status') }}</th>
                            <th class="text-center border-0">{{ translate('products count') }}</th>
                            <th class="text-center border-0">{{ translate('action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($brands as $key => $brand)
                            <tr>
                                <td>{{ $brands->firstItem() + $key }}</td>
                                <td>
                                    <div>
                                        <img class="img-vertical-150" src="{{ $brand->imageFullPath }}"
                                            alt="{{ translate('brand image') }}">
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body text-trim-25">
                                        {{ $brand['name'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $brand['slug'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <label class="toggle-switch my-0">
                                        <input type="checkbox" class="toggle-switch-input status-change-alert"
                                            id="featuredCheckbox{{ $brand->id }}"
                                            data-route="{{ route('admin.brand.featured', [$brand->id, $brand->is_featured ? 0 : 1]) }}"
                                            data-message="{{ $brand->is_featured ? translate('you_want_to_remove_this_brand_from_featured') : translate('you_want_to_add_this_brand_to_featured') }}"
                                            {{ $brand->is_featured ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <label class="toggle-switch my-0">
                                        <input type="checkbox" class="toggle-switch-input status-change-alert"
                                            id="stocksCheckbox{{ $brand->id }}"
                                            data-route="{{ route('admin.brand.status', [$brand->id, $brand->status ? 0 : 1]) }}"
                                            data-message="{{ $brand->status ? translate('you_want_to_disable_this_brand') : translate('you_want_to_active_this_brand') }}"
                                            {{ $brand->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.brand.show', $brand->id) }}" class="btn btn-sm btn-info">
                                        {{ $brand->products_count ?? $brand->products->count() }}
                                    </a>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="action-btn" href="{{ route('admin.brand.edit', [$brand['id']]) }}"><i
                                                class="tio-edit"></i></a>
                                        <a class="action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                            data-id="brand-{{ $brand['id'] }}"
                                            data-message="{{ translate('Want to delete this brand?') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                    <form action="{{ route('admin.brand.delete', [$brand['id']]) }}" method="post"
                                        id="brand-{{ $brand['id'] }}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="">
                {!! $brands->links('layouts/admin/partials/_pagination', ['perPage' => $perPage]) !!}
            </div>

            @if (count($brands) == 0)
                <div class="text-center p-4">
                    <img class="w-120px mb-3" src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                        alt="{{ translate('image') }}">
                    <p class="mb-0">{{ translate('No_data_to_show') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/brand.js') }}"></script>
    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        </script>
    @endif
@endpush
