@extends('layouts.admin.app')

@section('title', translate('Update banner'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--20" alt="{{ translate('banner') }}">
                </span>
                <span>
                    {{translate('Update Banner')}}
                </span>
            </h1>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.banner.update',[$banner['id']])}}" method="post" enctype="multipart/form-data" id="bannerForm" novalidate>
                    @csrf @method('put')

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="title">{{translate('title')}}</label>
                                        <input type="text" name="title" value="{{$banner['title']}}" class="form-control"
                                            placeholder="{{ translate('New banner') }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('display type')}} <span class="text-danger">*</span></label>
                                        <select name="display_type" class="form-control" id="displayType">
                                            <option value="single" {{$banner->display_type == 'single' ? 'selected' : ''}}>{{ translate('Single Banner (Static)') }}</option>
                                            <option value="carousel_horizontal" {{$banner->display_type == 'carousel_horizontal' ? 'selected' : ''}}>{{ translate('Horizontal Carousel (Slider)') }}</option>
                                            <option value="grid_2x2" {{$banner->display_type == 'grid_2x2' ? 'selected' : ''}}>{{ translate('Grid 2x2 (Four Banners)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('sections (where to show)')}} <span class="text-danger">*</span></label>
                                        <select name="sections[]" class="form-control js-select2-custom" multiple>
                                            @php
                                                $bannerSections = is_array($banner->sections) ? $banner->sections : json_decode($banner->sections ?? '[]', true);
                                            @endphp
                                            <option value="home_top" {{in_array('home_top', $bannerSections) ? 'selected' : ''}}>{{ translate('Home Page - Top') }}</option>
                                            <option value="home_middle" {{in_array('home_middle', $bannerSections) ? 'selected' : ''}}>{{ translate('Home Page - Middle') }}</option>
                                            <option value="home_bottom" {{in_array('home_bottom', $bannerSections) ? 'selected' : ''}}>{{ translate('Home Page - Bottom') }}</option>
                                            <option value="product_page" {{in_array('product_page', $bannerSections) ? 'selected' : ''}}>{{ translate('Product Page') }}</option>
                                            <option value="category_page" {{in_array('category_page', $bannerSections) ? 'selected' : ''}}>{{ translate('Category Page') }}</option>
                                            <option value="cart_page" {{in_array('cart_page', $bannerSections) ? 'selected' : ''}}>{{ translate('Cart Page') }}</option>
                                            <option value="checkout_page" {{in_array('checkout_page', $bannerSections) ? 'selected' : ''}}>{{ translate('Checkout Page') }}</option>
                                        </select>
                                        <small class="text-muted">{{ translate('You can select multiple sections') }}</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('sort order (position)')}}</label>
                                        <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"
                                            class="form-control" placeholder="{{ translate('0 = top, higher = lower') }}">
                                        <small class="text-muted">{{ translate('Lower number appears first') }}</small>
                                    </div>
                                </div>

                                <div class="col-12" id="clickActionDiv">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('click action')}} <span class="text-danger">*</span></label>
                                        <select name="click_action" class="form-control" id="clickAction">
                                            <option value="product" {{$banner->click_action == 'product' || ($banner->product_id && $banner->click_action != 'category' && $banner->click_action != 'link') ? 'selected' : ''}}>{{ translate('Go to Product Page') }}</option>
                                            <option value="category" {{$banner->click_action == 'category' || ($banner->category_id && $banner->click_action != 'product' && $banner->click_action != 'link') ? 'selected' : ''}}>{{ translate('Go to Category Page') }}</option>
                                            <option value="link" {{$banner->click_action == 'link' ? 'selected' : ''}}>{{ translate('External Link (URL)') }}</option>
                                            <option value="none" {{$banner->click_action == 'none' ? 'selected' : ''}}>{{ translate('Not Clickable (Static)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12" id="externalLinkDiv" style="display: {{$banner->click_action == 'link' ? 'block' : 'none'}};">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('external link (URL)')}}</label>
                                        <input type="url" name="external_link" class="form-control"
                                            placeholder="https://example.com" value="{{$banner->external_link ?? ''}}">
                                        <small class="text-muted">{{ translate('Full URL including https://') }}</small>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex flex-column justify-content-center h-100">
                                <div class="text-center mb-3">
                                    {{translate('banner')}} {{translate('image')}}
                                    <small class="text-danger">*</small>
                                </div>
                                <label class="upload--vertical">
                                    <input type="file" name="image" id="customFileEg1" class=""
                                           accept=".{{ implode(',.', array_column(IMAGE_EXTENSIONS, 'key')) }}, |image/*"
                                           hidden
                                           data-maxFileSize="{{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}">
                                    <img id="viewer"
                                         src="{{$banner->imageFullPath}}"
                                         alt="{{ translate('banner image') }}"/>
                                </label>

                                <p class="fs-10 m-0 text-center mt-3">
                                    {{ implode(', ', array_column(IMAGE_EXTENSIONS, 'key')) }} : Max {{ \App\CentralLogics\Helpers::readableUploadMaxFileSize('image') }}
                                    <span id="ratioHint" class="badge badge-soft-info p-1 fs-12 font-semibold">(2:1)</span>
                                </p>

                            </div>
                        </div>

                        <!-- Multi Items Section (Shows for carousel/grid) -->
                        <div class="col-12" id="multiItemsSection" style="display: {{$banner->display_type != 'single' ? 'block' : 'none'}};">
                            <div class="card mt-2">
                                <div class="card-header">
                                    <h5>{{ translate('Multiple Items (Products/Categories)') }}</h5>
                                    <small class="text-muted">{{ translate('Select multiple items for carousel or grid display') }}</small>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label">{{ translate('select items type') }}</label>
                                                <select name="multi_item_type" class="form-control" id="multiItemType">
                                                    <option value="products" id="optionProducts" {{$banner->product_ids && (is_array($banner->product_ids) ? count($banner->product_ids) : (is_string($banner->product_ids) ? count(json_decode($banner->product_ids, true)) : 0)) > 0 ? 'selected' : ''}}>{{ translate('Products') }}</option>
                                                    <option value="categories" id="optionCategories" {{$banner->category_ids && (is_array($banner->category_ids) ? count($banner->category_ids) : (is_string($banner->category_ids) ? count(json_decode($banner->category_ids, true)) : 0)) > 0 ? 'selected' : ''}}>{{ translate('Categories') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            @php
                                                // Safe decoding of JSON data for edit page
                                                $selectedProducts = [];
                                                $selectedCategories = [];

                                                if ($banner->product_ids) {
                                                    $selectedProducts = is_string($banner->product_ids) ? json_decode($banner->product_ids, true) : $banner->product_ids;
                                                    $selectedProducts = is_array($selectedProducts) ? $selectedProducts : [];
                                                }

                                                if ($banner->category_ids) {
                                                    $selectedCategories = is_string($banner->category_ids) ? json_decode($banner->category_ids, true) : $banner->category_ids;
                                                    $selectedCategories = is_array($selectedCategories) ? $selectedCategories : [];
                                                }

                                                $showProducts = count($selectedProducts) > 0;
                                                $showCategories = count($selectedCategories) > 0;
                                                $defaultShowProducts = (!$showProducts && !$showCategories) ? true : $showProducts;
                                            @endphp

                                            <div class="form-group mb-0" id="multiProductsDiv" style="display: {{$defaultShowProducts ? 'block' : 'none'}};">
                                                <label class="input-label">{{ translate('select multiple products') }}</label>
                                                <select name="product_ids[]" class="form-control js-select2-custom" id="multiProductSelect" multiple>
                                                    @foreach($products as $product)
                                                        <option value="{{$product['id']}}" {{in_array($product['id'], $selectedProducts) ? 'selected' : ''}}>
                                                            {{$product['name']}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group mb-0" id="multiCategoriesDiv" style="display: {{!$defaultShowProducts ? 'block' : 'none'}};">
                                                <label class="input-label">{{ translate('select multiple categories') }}</label>
                                                <select name="category_ids[]" class="form-control js-select2-custom" id="multiCategorySelect" multiple>
                                                    @foreach($categories as $category)
                                                        <option value="{{$category['id']}}" {{in_array($category['id'], $selectedCategories) ? 'selected' : ''}}>
                                                            {{$category['name']}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Single Item Section (Shows for single banner type) -->
                        <div class="col-12" id="singleItemSection" style="display: {{$banner->display_type == 'single' && in_array($banner->click_action, ['product', 'category']) ? 'block' : 'none'}};">
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
                                                    <option value="product" {{$banner->product_id || !$banner->category_id ? 'selected' : ''}}>{{ translate('product') }}</option>
                                                    <option value="category" {{$banner->category_id ? 'selected' : ''}}>{{ translate('category') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0" id="type-product" style="display: {{$banner->product_id || !$banner->category_id ? 'block' : 'none'}};">
                                                <label class="input-label">{{ translate('product') }}</label>
                                                <select name="product_id" class="form-control js-select2-custom">
                                                    <option value="">{{ translate('Select product') }}</option>
                                                    @foreach($products as $product)
                                                        <option value="{{$product['id']}}" {{$banner->product_id == $product['id'] ? 'selected' : ''}}>
                                                            {{$product['name']}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-0" id="type-category" style="display: {{$banner->category_id ? 'block' : 'none'}};">
                                                <label class="input-label">{{ translate('category') }}</label>
                                                <select name="category_id" class="form-control js-select2-custom">
                                                    <option value="">{{ translate('Select category') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{$category['id']}}" {{$banner->category_id == $category['id'] ? 'selected' : ''}}>{{$category['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{translate('reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('update')}}</button>
                        </div>
                    </div>
                </form>
            </div>
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
