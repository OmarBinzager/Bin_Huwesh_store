@extends('layouts.admin.app')

@section('title', translate('Currency Management'))

@section('content')
<div class="content container-fluid">
    @include('admin-views.business-settings.partial.business-settings-navmenu')

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <span class="mr-3 fs-30">💱</span>
                            <div>
                                <h4 class="mb-1 text-primary">{{ translate('System Base Currency') }}: <strong>{{ $baseCurrency->currency_code ?? 'YER' }} ({{ $baseCurrency->currency_symbol ?? '﷼' }})</strong></h4>
                                <p class="mb-0 text-muted fs-13">
                                    {{ translate('All product prices and core accounting in the database are stored in this base currency.') }}
                                    <br>
                                    <span class="text-dark font-weight-bold">{{ translate('Exchange Rate Rule:') }}</span> 
                                    {{ translate('1 Foreign Unit = [Rate] in Base Currency') }} ({{ $baseCurrency->currency_code ?? 'YER' }}). 
                                    <span class="text-info">({{ translate('Example: 1 SAR = 140 YER, 1 USD = 530 YER') }})</span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-soft-success px-3 py-2 fs-14">
                                <i class="tio-checkmark-circle-outlined"></i> {{ $activeCurrencies->count() }} {{ translate('Active for Customers') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Currencies Rate Adjustment Card -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="tio-tune mr-2 text-primary"></i> {{ translate('Customer Currencies & Active Exchange Rates') }}
            </h5>
            <small class="text-muted">{{ translate('These are the currencies customers can select in the mobile app and website.') }}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Currency') }}</th>
                        <th>{{ translate('Country') }}</th>
                        <th>{{ translate('Symbol') }}</th>
                        <th>{{ translate('Position') }}</th>
                        <th>{{ translate('Exchange Rate') }} <small class="text-primary font-weight-bold">({{ translate('in') }} {{ $baseCurrency->currency_code ?? 'Base' }})</small></th>
                        <th>{{ translate('Role') }}</th>
                        <th class="text-center">{{ translate('Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeCurrencies as $currency)
                        <tr>
                            <td>
                                <strong>{{ $currency->currency_code }}</strong>
                            </td>
                            <td>{{ $currency->country ?? '-' }}</td>
                            <td>{{ $currency->currency_symbol }}</td>
                            <td>
                                <span class="badge badge-soft-info">{{ ucfirst($currency->symbol_position ?? 'right') }}</span>
                            </td>
                            <td>
                                @if($currency->is_base)
                                    <span class="font-weight-bold text-success">1.000000 ({{ translate('Base Anchor') }})</span>
                                @else
                                    <form action="{{ route('admin.business-settings.currency-update-rate', [$currency->id]) }}" method="POST" class="d-flex align-items-center" style="max-width: 250px;">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.000001" min="0.000001" name="exchange_rate" class="form-control" value="{{ $currency->exchange_rate }}" required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary" title="{{ translate('Save Rate') }}">
                                                    <i class="tio-save"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($currency->is_base)
                                    <span class="badge badge-success px-2 py-1"><i class="tio-verified"></i> {{ translate('Base Currency') }}</span>
                                @else
                                    <form action="{{ route('admin.business-settings.currency-set-base', [$currency->id]) }}" method="POST" onsubmit="return confirm('{{ translate('Are you sure you want to make this the store base currency? All future exchange rates will be relative to it.') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-primary">
                                            {{ translate('Make Base') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-center">
                                <label class="toggle-switch toggle-switch-sm">
                                    <input type="checkbox" class="toggle-switch-input status-toggle" data-id="{{ $currency->id }}" data-url="{{ route('admin.business-settings.currency-status', [$currency->id]) }}" {{ $currency->status ? 'checked' : '' }} {{ $currency->is_base ? 'disabled' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <a class="btn btn-outline-info btn-sm square-btn mr-1" title="{{ translate('Edit') }}" href="{{ route('admin.business-settings.currency-update', [$currency->id]) }}">
                                        <i class="tio-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Currency Form Card -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
                <i class="tio-add mr-2 text-primary"></i> {{ translate('Add New Currency') }}
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.business-settings.currency-add') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Country Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="country" class="form-control" placeholder="{{ translate('e.g. Saudi Arabia, Yemen, USA') }}" required>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Currency Code (ISO)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="currency_code" class="form-control text-uppercase" placeholder="{{ translate('e.g. SAR, USD, YER') }}" maxlength="10" required>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Currency Symbol') }} <span class="text-danger">*</span></label>
                        <input type="text" name="currency_symbol" class="form-control" placeholder="{{ translate('e.g. ر.س, $, ﷼') }}" required>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Symbol Position') }}</label>
                        <select name="symbol_position" class="form-control">
                            <option value="right">{{ translate('Right (100 ﷼)') }}</option>
                            <option value="left">{{ translate('Left ($100)') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Decimal Digits') }}</label>
                        <input type="number" name="decimal_digits" class="form-control" value="2" min="0" max="6">
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">
                            {{ translate('Exchange Rate (in Base Units)') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.000001" min="0.000001" name="exchange_rate" class="form-control" placeholder="{{ translate('e.g. 140 for SAR if Base is YER') }}" required>
                        <small class="text-muted">{{ translate('1 unit of this currency = How many units of ') }} {{ $baseCurrency->currency_code ?? 'Base' }}?</small>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3 d-flex align-items-center pt-4">
                        <label class="toggle-switch toggle-switch-sm d-flex align-items-center">
                            <input type="checkbox" name="status" value="1" class="toggle-switch-input" checked>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                            <span class="ml-2 font-weight-bold">{{ translate('Enable for Users') }}</span>
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 d-flex align-items-end justify-content-end mb-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="tio-add"></i> {{ translate('Save Currency') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- All Available Currencies Table Card -->
    <div class="card">
        <div class="card-header bg-white py-3 flex-wrap justify-content-between align-items-center">
            <h5 class="card-title mb-2 mb-md-0">
                <i class="tio-table mr-2 text-primary"></i> {{ translate('All Available System Currencies') }}
                <span class="badge badge-soft-secondary ml-2">{{ $currencies->total() }}</span>
            </h5>
            <form action="{{ route('admin.business-settings.currency-add') }}" method="GET" class="d-flex align-items-center">
                <div class="input-group input-group-sm">
                    <input type="search" name="search" class="form-control" placeholder="{{ translate('Search Code, Name, Symbol...') }}" value="{{ $search ?? '' }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Country') }}</th>
                        <th>{{ translate('Code') }}</th>
                        <th>{{ translate('Symbol') }}</th>
                        <th>{{ translate('Exchange Rate') }}</th>
                        <th class="text-center">{{ translate('Status (User Visible)') }}</th>
                        <th class="text-center">{{ translate('Base Currency') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currencies as $key => $currency)
                        <tr>
                            <td>{{ $currencies->firstItem() + $key }}</td>
                            <td>{{ $currency->country ?? '-' }}</td>
                            <td><strong>{{ $currency->currency_code }}</strong></td>
                            <td>{{ $currency->currency_symbol }}</td>
                            <td>
                                @if($currency->is_base)
                                    <span class="text-success font-weight-bold">1.000000</span>
                                @else
                                    {{ number_format($currency->exchange_rate, 4) }}
                                @endif
                            </td>
                            <td class="text-center">
                                <label class="toggle-switch toggle-switch-sm">
                                    <input type="checkbox" class="toggle-switch-input status-toggle" data-id="{{ $currency->id }}" data-url="{{ route('admin.business-settings.currency-status', [$currency->id]) }}" {{ $currency->status ? 'checked' : '' }} {{ $currency->is_base ? 'disabled' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>
                            <td class="text-center">
                                @if($currency->is_base)
                                    <span class="badge badge-success px-2 py-1"><i class="tio-checkmark-circle"></i> {{ translate('Base') }}</span>
                                @else
                                    <form action="{{ route('admin.business-settings.currency-set-base', [$currency->id]) }}" method="POST" onsubmit="return confirm('{{ translate('Make this the system base currency?') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary">
                                            {{ translate('Set as Base') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <a class="btn btn-outline-info btn-sm square-btn mr-1" title="{{ translate('Edit') }}" href="{{ route('admin.business-settings.currency-update', [$currency->id]) }}">
                                        <i class="tio-edit"></i>
                                    </a>
                                    @if(!$currency->is_base)
                                        <form action="{{ route('admin.business-settings.currency-delete', [$currency->id]) }}" method="POST" onsubmit="return confirm('{{ translate('Are you sure you want to delete this currency?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete') }}">
                                                <i class="tio-delete"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                {{ translate('No currencies found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-end">
                {!! $currencies->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    $(document).on('change', '.status-toggle', function () {
        let url = $(this).data('url');
        let $checkbox = $(this);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                }
            },
            error: function (xhr) {
                let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ translate("An error occurred") }}';
                toastr.error(msg);
                $checkbox.prop('checked', !$checkbox.prop('checked'));
            }
        });
    });
</script>
@endpush
