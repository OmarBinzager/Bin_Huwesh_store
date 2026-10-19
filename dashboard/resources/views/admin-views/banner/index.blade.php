@extends('layouts.admin.app')

@section('title', translate('Add new banner'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--20"
                        alt="{{ translate('banner') }}">
                </span>
                <span>
                    {{ translate('banner setup') }}
                </span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.banner.store') }}" method="post" enctype="multipart/form-data"
                    id="bannerForm" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="title">{{ translate('title') }}</label>
                                        <input type="text" name="title" value="{{ old('title') }}"
                                            class="form-control" placeholder="{{ translate('New banner') }}" maxlength="255">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('display type') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="display_type" class="form-control" id="displayType">
                                            <option value="single">{{ translate('Single Banner (Static)') }}</option>
                                            <option value="carousel_horizontal">
                                                {{ translate('Horizontal Carousel (Slider)') }}</option>
                                            <option value="grid_2x2">{{ translate('Grid 2x2 (Four Banners)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('sections (where to show)') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="sections[]" class="form-control js-select2-custom" multiple>
                                            <option value="home_top">{{ translate('Home Page - Top') }}</option>
                                            <option value="home_middle">{{ translate('Home Page - Middle') }}</option>
                                            <option value="home_bottom">{{ translate('Home Page - Bottom') }}</option>
                                            <option value="product_page">{{ translate('Product Page') }}</option>
                                            <option value="category_page">{{ translate('Category Page') }}</option>
                                            <option value="cart_page">{{ translate('Cart Page') }}</option>
                                            <option value="checkout_page">{{ translate('Checkout Page') }}</option>
                                        </select>
                                        <small
                                            class="text-muted">{{ translate('You can select multiple sections') }}</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('sort order (position)') }}</label>
                                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                                            class="form-control" placeholder="{{ translate('0 = top, higher = lower') }}">
                                        <small class="text-muted">{{ translate('Lower number appears first') }}</small>
                                    </div>
                                </div>

                                <div class="col-12" id="clickActionDiv">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('click action') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="click_action" class="form-control" id="clickAction">
                                            <option value="product">{{ translate('Go to Product Page') }}</option>
                                            <option value="category">{{ translate('Go to Category Page') }}</option>
                                            <option value="link">{{ translate('External Link (URL)') }}</option>
                                            <option value="none">{{ translate('Not Clickable (Static)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12" id="externalLinkDiv" style="display: none;">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('external link (URL)') }}</label>
                                        <input type="url" name="external_link" class="form-control"
                                            placeholder="https://example.com">
                                        <small class="text-muted">{{ translate('Full URL including https://') }}</small>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex flex-column justify-content-center h-100">
                                <h5 class="text-center mb-3 text--title text-capitalize">
                                    {{ translate('banner') }} {{ translate('image') }}
                                    <small class="text-danger">*</small>
                                </h5>
                                <label class="upload--vertical">
                                    <input type="file" name="image" id="customFileEg1" class=""
                                        accept=".{{ implode(',.', array_column(IMAGE_EXTENSIONS, 'key')) }}, |image/*"
                                        hidden
                                        data-maxFileSize="{{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}">
                                    <img id="viewer" src="{{ asset('public/assets/admin/img/upload-vertical.png') }}"
                                        alt="{{ translate('banner image') }}" />
                                </label>
                                <p class="fs-10 m-0 text-center mt-3">
                                    {{ implode(', ', array_column(IMAGE_EXTENSIONS, 'key')) }} : Max
                                    {{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}
                                    <span id="ratioHint" class="badge badge-soft-info p-1 fs-12 font-semibold">(2:1)</span>
                                </p>
                            </div>
                        </div>

                        <!-- Multi Items Section (Shows for carousel/grid) -->
                        <div class="col-12" id="multiItemsSection" style="display: none;">
                            <div class="card mt-2">
                                <div class="card-header">
                                    <h5>{{ translate('Multiple Items (Products/Categories)') }}</h5>
                                    <small
                                        class="text-muted">{{ translate('Select multiple items for carousel or grid display') }}</small>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label">{{ translate('select items type') }}</label>
                                                <select name="multi_item_type" class="form-control" id="multiItemType">
                                                    <option value="products">{{ translate('Products') }}</option>
                                                    <option value="categories">{{ translate('Categories') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0" id="multiProductsDiv">
                                                <label
                                                    class="input-label">{{ translate('select multiple products') }}</label>
                                                <select name="product_ids[]" class="form-control js-select2-custom"
                                                    id="multiProductSelect" multiple>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product['id'] }}">{{ $product['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-0" id="multiCategoriesDiv" style="display: none;">
                                                <label
                                                    class="input-label">{{ translate('select multiple categories') }}</label>
                                                <select name="category_ids[]" class="form-control js-select2-custom"
                                                    id="multiCategorySelect" multiple>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category['id'] }}">{{ $category['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Single Item Section (Shows for single banner type when product or category is chosen) -->
                        <div class="col-12" id="singleItemSection">
                            <div class="card mt-2">
                                <div class="card-header">
                                    <h5>{{ translate('Banner Target (Product or Category)') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label">{{ translate('item type') }}</label>
                                                <select name="item_type" class="form-control show-item" id="itemType">
                                                    <option value="product">{{ translate('product') }}</option>
                                                    <option value="category">{{ translate('category') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0" id="type-product">
                                                <label class="input-label">{{ translate('product') }}</label>
                                                <select name="product_id" class="form-control js-select2-custom">
                                                    <option value="">{{ translate('Select product') }}</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product['id'] }}">{{ $product['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-0" id="type-category" style="display: none">
                                                <label class="input-label">{{ translate('category') }}</label>
                                                <select name="category_id" class="form-control js-select2-custom">
                                                    <option value="">{{ translate('Select category') }}</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category['id'] }}">{{ $category['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                    <h5 class="mb-0"> {{ translate('Banner List') }}
                        <span class="badge badge-soft-dark rounded-pill fs-10 ml-1">{{ $banners->total() }}</span>
                    </h5>
                </div>

                <div class="d-flex flex-sm-nowrap flex-wrap gap-sm-3 gap-3">
                    <form action="{{ request()->url() }}" method="GET">
                        @foreach (request()->except('search', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h-30"
                                placeholder="{{ translate('Search by ID or Title') }}" aria-label="Search"
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
                            <th class="border-0">{{ translate('banner image') }}</th>
                            <th class="border-0">{{ translate('title') }}</th>
                            <th class="border-0">{{ translate('display type') }}</th>
                            <th class="border-0">{{ translate('sections') }}</th>
                            <th class="border-0">{{ translate('click action') }}</th>
                            <th class="text-center border-0">{{ translate('status') }}</th>
                            <th class="text-center border-0">{{ translate('action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($banners as $key => $banner)
                            <tr>
                                <td>{{ $banners->firstItem() + $key }}</td>
                                <td>
                                    <div>
                                        <img class="img-vertical-150" src="{{ $banner->imageFullPath }}"
                                            alt="{{ translate('banner image') }}">
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body text-trim-25">
                                        {{ $banner['title'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        @if ($banner->display_type == 'single')
                                            {{ translate('Single') }}
                                        @elseif($banner->display_type == 'carousel_horizontal')
                                            {{ translate('Horizontal Carousel') }}
                                        @elseif($banner->display_type == 'carousel_vertical')
                                            {{ translate('Vertical Carousel') }}
                                        @elseif($banner->display_type == 'grid_2x2')
                                            {{ translate('Grid 2x2') }}
                                        @endif
                                    </span>
                                    @if ($banner->isMultiItem())
                                        <br>
                                        <small class="text-muted">
                                            {{ translate('Items:') }}
                                            @php
                                                // تحويل JSON إلى array إذا كان string
                                                $productIds = is_string($banner->product_ids)
                                                    ? json_decode($banner->product_ids, true)
                                                    : $banner->product_ids;
                                                $categoryIds = is_string($banner->category_ids)
                                                    ? json_decode($banner->category_ids, true)
                                                    : $banner->category_ids;
                                            @endphp
                                            @if ($productIds && count($productIds) > 0)
                                                {{ count($productIds) }} {{ translate('products') }}
                                            @elseif($categoryIds && count($categoryIds) > 0)
                                                {{ count($categoryIds) }} {{ translate('categories') }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $sections = is_array($banner->sections)
                                            ? $banner->sections
                                            : json_decode($banner->sections ?? '[]', true);
                                    @endphp
                                    @foreach ($sections as $section)
                                        <span class="badge badge-soft-secondary mb-1">
                                            {{ translate(str_replace('_', ' ', ucfirst($section))) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge badge-warning">
                                        @if ($banner->click_action == 'product')
                                            {{ translate('Product') }}
                                        @elseif($banner->click_action == 'category')
                                            {{ translate('Category') }}
                                        @elseif($banner->click_action == 'link')
                                            {{ translate('External Link') }}
                                        @else
                                            {{ translate('Not Clickable') }}
                                        @endif
                                    </span>
                                    @if ($banner->click_action == 'link' && $banner->external_link)
                                        <br>
                                        <small class="text-truncate"
                                            style="max-width: 150px;">{{ $banner->external_link }}</small>
                                    @endif
                                </td>
                                <td>
                                    <label class="toggle-switch my-0">
                                        <input type="checkbox" class="toggle-switch-input status-change-alert"
                                            id="stocksCheckbox{{ $banner->id }}"
                                            data-route="{{ route('admin.banner.status', [$banner->id, $banner->status ? 0 : 1]) }}"
                                            data-message="{{ $banner->status ? translate('you_want_to_disable_this_banner') : translate('you_want_to_active_this_banner') }}"
                                            {{ $banner->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="action-btn"
                                            href="{{ route('admin.banner.edit', [$banner['id']]) }}"><i
                                                class="tio-edit"></i></a>
                                        <a class="action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                            data-id="banner-{{ $banner['id'] }}"
                                            data-message="{{ translate('Want to delete this') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                    <form action="{{ route('admin.banner.delete', [$banner['id']]) }}" method="post"
                                        id="banner-{{ $banner['id'] }}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="">
                {!! $banners->links('layouts/admin/partials/_pagination', ['perPage' => $perPage]) !!}
            </div>

            @if (count($banners) == 0)
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
    <script src="{{ asset('public/assets/admin/js/banner.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Function to synchronize all dynamic fields
            function updateDynamicFields() {
                var displayType = $('#displayType').val();

                // Multi items section is optional/hidden unless explicitly needed
                $('#multiItemsSection').hide();

                // Click action is ALWAYS active for all display types
                $('#clickActionDiv').show();

                var clickAction = $('#clickAction').val();

                if (clickAction === 'link') {
                    $('#externalLinkDiv').show();
                    $('#singleItemSection').hide();
                } else if (clickAction === 'product') {
                    $('#externalLinkDiv').hide();
                    $('#singleItemSection').show();
                    $('#itemType').val('product');
                    $('#type-product').show();
                    $('#type-category').hide();
                } else if (clickAction === 'category') {
                    $('#externalLinkDiv').hide();
                    $('#singleItemSection').show();
                    $('#itemType').val('category');
                    $('#type-product').hide();
                    $('#type-category').show();
                } else {
                    // none (static)
                    $('#externalLinkDiv').hide();
                    $('#singleItemSection').hide();
                }



                // Dynamic aspect ratio hint
                var sections = $('select[name="sections[]"]').val() || [];
                if (displayType === 'carousel_horizontal') {
                    $('#ratioHint').text('(2:1) ~ 800x400 px');
                } else if (displayType === 'grid_2x2') {
                    $('#ratioHint').text('(1:1) ~ 500x500 px');
                } else if (sections.indexOf('home_top') !== -1) {
                    $('#ratioHint').text('(4:1) ~ 1200x300 px');
                } else {
                    $('#ratioHint').text('(2:1) ~ 800x400 px');
                }
            }

            // Sync when itemType is manually changed
            $('#itemType').on('change', function() {
                var type = $(this).val();
                if (type === 'product') {
                    $('#clickAction').val('product');
                    $('#type-product').show();
                    $('#type-category').hide();
                } else {
                    $('#clickAction').val('category');
                    $('#type-product').hide();
                    $('#type-category').show();
                }
            });

            // Event listeners
            $('#displayType').on('change', updateDynamicFields);
            $('#clickAction').on('change', updateDynamicFields);
            $('select[name="sections[]"]').on('change', updateDynamicFields);

            // Initial call
            updateDynamicFields();

            // Form validation before submit
            $('#bannerForm').on('submit', function(e) {
                var title = $.trim($('input[name="title"]').val());
                if (!title) {
                    e.preventDefault();
                    toastr.error("{{ translate('Title is required!') }}");
                    $('input[name="title"]').focus();
                    return false;
                }

                var image = $('#customFileEg1')[0]?.files?.length;
                if (!image) {
                    e.preventDefault();
                    toastr.error("{{ translate('Banner image is required!') }}");
                    return false;
                }

                var sections = $('select[name="sections[]"]').val();
                if (!sections || sections.length === 0) {
                    e.preventDefault();
                    toastr.error("{{ translate('Please select at least one section') }}");
                    return false;
                }

                var clickAction = $('#clickAction').val();
                if (clickAction === 'product') {
                    var productId = $('select[name="product_id"]').val();
                    if (!productId) {
                        e.preventDefault();
                        toastr.error("{{ translate('Please select a product') }}");
                        return false;
                    }
                } else if (clickAction === 'category') {
                    var categoryId = $('select[name="category_id"]').val();
                    if (!categoryId) {
                        e.preventDefault();
                        toastr.error("{{ translate('Please select a category') }}");
                        return false;
                    }
                } else if (clickAction === 'link') {
                    var externalLink = $.trim($('input[name="external_link"]').val());
                    if (!externalLink) {
                        e.preventDefault();
                        toastr.error("{{ translate('Please enter an external link') }}");
                        $('input[name="external_link"]').focus();
                        return false;
                    }
                }

                return true;
            });

            // Reset handler
            $('#bannerForm').on('reset', function() {
                setTimeout(function() {
                    $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload-vertical.png') }}");
                    $('select[name="sections[]"]').val(null).trigger('change');
                    $('#multiProductSelect').val(null).trigger('change');
                    $('#multiCategorySelect').val(null).trigger('change');
                    $('select[name="product_id"]').val(null).trigger('change');
                    $('select[name="category_id"]').val(null).trigger('change');
                    updateDynamicFields();
                }, 50);
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
