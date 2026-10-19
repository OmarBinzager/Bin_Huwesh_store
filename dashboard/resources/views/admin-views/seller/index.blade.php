@extends('layouts.admin.app')

@section('title', translate('Sellers'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/seller.png') }}" class="w--20"
                        alt="{{ translate('seller') }}">
                </span>
                <span>
                    {{ translate('seller setup') }}
                </span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.seller.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('seller name') }}</label>
                                        <input type="text" name="name" value="{{ old('name') }}"
                                            class="form-control" placeholder="{{ translate('New seller') }}" maxlength="255"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('email') }}</label>
                                        <input type="email" name="email" value="{{ old('email') }}"
                                            class="form-control" placeholder="{{ translate('seller@example.com') }}"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('phone') }}</label>
                                        <input type="tel" name="phone" value="{{ old('phone') }}"
                                            class="form-control" placeholder="{{ translate('+1234567890') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('description') }}</label>
                                        <textarea name="description" class="form-control" rows="2"
                                            placeholder="{{ translate('Seller description (optional)') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('address') }}</label>
                                        <textarea name="address" class="form-control" rows="2"
                                            placeholder="{{ translate('Seller address (optional)') }}">{{ old('address') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('city') }}</label>
                                        <input type="text" name="city" value="{{ old('city') }}"
                                            class="form-control" placeholder="{{ translate('City') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('state') }}</label>
                                        <input type="text" name="state" value="{{ old('state') }}"
                                            class="form-control" placeholder="{{ translate('State') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('country') }}</label>
                                        <input type="text" name="country" value="{{ old('country') }}"
                                            class="form-control" placeholder="{{ translate('Country') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('zip code') }}</label>
                                        <input type="text" name="zip_code" value="{{ old('zip_code') }}"
                                            class="form-control" placeholder="{{ translate('Zip code') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('website') }}</label>
                                        <input type="url" name="website" value="{{ old('website') }}"
                                            class="form-control" placeholder="{{ translate('https://example.com') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="1">{{ translate('active') }}</option>
                                            <option value="0">{{ translate('inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('featured') }}</label>
                                        <select name="is_featured" class="form-control">
                                            <option value="0">{{ translate('no') }}</option>
                                            <option value="1">{{ translate('yes') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('commission rate') }} (%)</label>
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="commission_rate" value="{{ old('commission_rate', 0) }}"
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
                                    <img id="viewer" src="{{ asset('public/assets/admin/img/upload-vertical.png') }}"
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
                    <h5 class="mb-0"> {{ translate('Seller List') }}
                        <span class="badge badge-soft-dark rounded-pill fs-10 ml-1">{{ $sellers->total() }}</span>
                    </h5>
                </div>

                <div class="d-flex flex-sm-nowrap flex-wrap gap-sm-3 gap-3">
                    <form action="{{ request()->url() }}" method="GET">
                        @foreach (request()->except('search', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h-30"
                                placeholder="{{ translate('Search by ID, Name or Email') }}" aria-label="Search"
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
                            <th class="border-0">{{ translate('logo') }}</th>
                            <th class="border-0">{{ translate('seller name') }}</th>
                            <th class="border-0">{{ translate('email') }}</th>
                            <th class="border-0">{{ translate('phone') }}</th>
                            <th class="border-0">{{ translate('commission') }}</th>
                            <th class="text-center border-0">{{ translate('featured') }}</th>
                            <th class="text-center border-0">{{ translate('status') }}</th>
                            <th class="text-center border-0">{{ translate('products count') }}</th>
                            <th class="text-center border-0">{{ translate('action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($sellers as $key => $seller)
                            <tr>
                                <td>{{ $sellers->firstItem() + $key }}</td>
                                <td>
                                    <div>
                                        <img class="img-vertical-100" src="{{ $seller->logoFullPath }}"
                                            alt="{{ translate('seller logo') }}"
                                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body text-trim-25">
                                        {{ $seller['name'] }}
                                    </span>
                                </td>
                                <td>{{ $seller['email'] }}</td>
                                <td>{{ $seller['phone'] ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-soft-primary">
                                        {{ $seller['commission_rate'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <label class="toggle-switch my-0">
                                        <input type="checkbox" class="toggle-switch-input status-change-alert"
                                            id="featuredCheckbox{{ $seller->id }}"
                                            data-route="{{ route('admin.seller.featured', [$seller->id, $seller->is_featured ? 0 : 1]) }}"
                                            data-message="{{ $seller->is_featured ? translate('you_want_to_remove_this_seller_from_featured') : translate('you_want_to_add_this_seller_to_featured') }}"
                                            {{ $seller->is_featured ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <label class="toggle-switch my-0">
                                        <input type="checkbox" class="toggle-switch-input status-change-alert"
                                            id="stocksCheckbox{{ $seller->id }}"
                                            data-route="{{ route('admin.seller.status', [$seller->id, $seller->status ? 0 : 1]) }}"
                                            data-message="{{ $seller->status ? translate('you_want_to_disable_this_seller') : translate('you_want_to_active_this_seller') }}"
                                            {{ $seller->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.seller.show', $seller->id) }}" class="btn btn-sm btn-info">
                                        {{ $seller->products_count ?? $seller->products->count() }}
                                    </a>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="action-btn" href="{{ route('admin.seller.edit', [$seller['id']]) }}"><i
                                                class="tio-edit"></i></a>
                                        <a class="action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                            data-id="seller-{{ $seller['id'] }}"
                                            data-message="{{ translate('Want to delete this seller?') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                    <form action="{{ route('admin.seller.delete', [$seller['id']]) }}" method="post"
                                        id="seller-{{ $seller['id'] }}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="">
                {!! $sellers->links('layouts/admin/partials/_pagination', ['perPage' => $perPage]) !!}
            </div>

            @if (count($sellers) == 0)
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
    <script>
        // Preview image before upload
        document.getElementById('customFileEg1')?.onchange = function(evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('viewer').src = URL.createObjectURL(file);
            }
        };
    </script>

    <script>
        $('.status-change-alert').on('change', function() {
            let route = $(this).data('route');
            let message = $(this).data('message');

            Swal.fire({
                title: '{{ translate('Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('No') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                } else {
                    $(this).prop('checked', !$(this).prop('checked'));
                }
            });
        });

        $('.form-alert').on('click', function() {
            let id = $(this).data('id');
            let message = $(this).data('message');

            Swal.fire({
                title: '{{ translate('Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('No') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit();
                }
            });
        });
    </script>

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        </script>
    @endif
@endpush
