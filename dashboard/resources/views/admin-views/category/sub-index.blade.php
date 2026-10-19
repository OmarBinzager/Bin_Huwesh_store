@extends('layouts.admin.app')

@section('title', translate('Add new sub category'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--24"
                        alt="{{ translate('category') }}">
                </span>
                <span>
                    {{ translate('sub_category_setup') }}
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.category.store') }}" method="post" id="sub_category_form">
                    @csrf

                    @php
                        $languages = Helpers::get_business_settings('language');
                        $defaultLanguage = Helpers::get_default_language();
                    @endphp

                    @if ($languages && array_key_exists('code', $languages[0]))
                        <ul class="nav nav-tabs mb-4 d-inline-flex">
                            @foreach ($languages as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link {{ $lang['default'] == true ? 'active' : '' }}"
                                        href="#"
                                        id="{{ $lang['code'] }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang['code']) . '(' . strtoupper($lang['code']) . ')' }}</a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="row">
                            @foreach ($languages as $lang)
                                <div class="col-sm-6 {{ $lang['default'] == false ? 'd-none' : '' }} lang_form"
                                    id="{{ $lang['code'] }}-form">
                                    <label class="form-label"
                                        for="exampleFormControlInput1">{{ translate('sub_category') }}
                                        {{ translate('name') }} ({{ strtoupper($lang['code']) }})
                                        @if ($lang['code'] == 'en')
                                            <span class="input-label-secondary text-danger">*</span>
                                        @endif
                                    </label>

                                    <input type="text" name="name[]" class="form-control" maxlength="255"
                                        placeholder="{{ translate('New Sub Category') }}"
                                        @if ($lang['status'] == true) oninvalid="document.getElementById('{{ $lang['code'] }}-link').click()" @endif>

                                    @if ($lang['code'] == 'en')
                                        <span class="error-text d-flex justify-content-end fs-12px text-danger"
                                            data-error="name.0"></span>
                                    @endif
                                </div>

                                <input type="hidden" name="lang[]" value="{{ $lang['code'] }}">
                            @endforeach
                        @else
                            <div class="col-sm-6 lang_form" id="{{ $defaultLanguage }}-form">
                                <label class="form-label" for="exampleFormControlInput1">{{ translate('sub_category') }}
                                    {{ translate('name') }}({{ strtoupper($defaultLanguage) }})</label>
                                <input type="text" name="name[]" class="form-control"
                                    placeholder="{{ translate('New Sub Category') }}">
                            </div>
                            <input type="hidden" name="lang[]" value="{{ $defaultLanguage }}">
                    @endif

                    <input name="position" value="1" hidden>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="form-label" for="exampleFormControlSelect1">{{ translate('main') }}
                                {{ translate('category') }}<span class="input-label-secondary text-danger">*</span></label>

                            <select id="exampleFormControlSelect1" name="parent_id" class="form-control" required>
                                @foreach (\App\Model\Category::where(['position' => 0])->get() as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="btn--container justify-content-end">
                            <a href="" class="btn btn--reset min-w-120px">{{ translate('reset') }}</a>
                            <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card--header order-top">
            <div class="d-flex gap-2 align-items-center">
                <h5 class="mb-0"> {{ translate('Sub Category Table') }}
                    <span class="badge badge-soft-dark rounded-pill fs-10 ml-1">{{ $categories->total() }}</span>
                </h5>
            </div>

            <div class="d-flex flex-sm-nowrap flex-wrap gap-sm-3 gap-3">
                <form action="{{ request()->url() }}" method="GET">
                    @foreach (request()->except('search', 'page') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="input-group">
                        <input id="datatableSearch_" type="search" name="search" class="form-control h-30"
                            placeholder="{{ translate('Search by Name') }}" aria-label="Search"
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
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('#') }}</th>
                        <th>{{ translate('main') }} {{ translate('category') }}</th>
                        <th>{{ translate('sub_category') }}</th>
                        <th>{{ translate('status') }}</th>
                        <th class="text-center">{{ translate('action') }}</th>
                    </tr>

                </thead>

                <tbody id="set-rows">
                    @foreach ($categories as $key => $category)
                        <tr>
                            <td>{{ $categories->firstItem() + $key }}</td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $category->parent['name'] }}
                                </span>
                            </td>

                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $category['name'] }}
                                </span>
                            </td>

                            <td>

                                <label class="toggle-switch">
                                    <input type="checkbox" class="toggle-switch-input status-change-alert"
                                        id="stocksCheckbox{{ $category->id }}"
                                        data-route="{{ route('admin.category.status', [$category->id, $category->status ? 0 : 1]) }}"
                                        data-message="{{ $category->status ? translate('you_want_to_disable_this_category') : translate('you_want_to_active_this_category') }}"
                                        {{ $category->status ? 'checked' : '' }}>
                                    <span class="toggle-switch-label text">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>

                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="action-btn" href="{{ route('admin.category.edit', [$category['id']]) }}">
                                        <i class="tio-edit"></i></a>
                                    <a class="action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                        data-id="category-{{ $category['id'] }}"
                                        data-message="{{ translate('Want to delete this') }}?">
                                        <i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                                <form action="{{ route('admin.category.delete', [$category['id']]) }}" method="post"
                                    id="category-{{ $category['id'] }}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (count($categories) == 0)
            <div class="text-center p-4">
                <img class="w-120px mb-3" src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                    alt="{{ translate('image') }}">
                <p class="mb-0">{{ translate('No_data_to_show') }}</p>
            </div>
        @endif

        <div class="">
            {!! $categories->links('layouts/admin/partials/_pagination', ['perPage' => $perPage]) !!}
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(".lang_link").click(function(e) {
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            $("#" + lang + "-form").removeClass('d-none');
            if (lang == '{{ $defaultLanguage }}') {
                $(".from_part_2").removeClass('d-none');
            } else {
                $(".from_part_2").addClass('d-none');
            }
        });

        submitByAjax('#sub_category_form', {
            hasEditors: false,
            languages: @json($languages ?? []),
            successMessage: '{{ translate('Sub category added successfully!') }}',
            redirectUrl: '{{ route('admin.category.add-sub-category') }}'
        });
    </script>
@endpush
