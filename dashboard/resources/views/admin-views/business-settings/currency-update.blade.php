@extends('layouts.admin.app')

@section('title', translate('Update Currency'))

@section('content')
<div class="content container-fluid">
    @include('admin-views.business-settings.partial.business-settings-navmenu')

    <div class="card">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
                <i class="tio-edit mr-2 text-primary"></i> {{ translate('Update Currency') }}: <strong>{{ $currency->currency_code }}</strong>
            </h5>
            <a href="{{ route('admin.business-settings.currency-add') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> {{ translate('Back to List') }}
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.business-settings.currency-update', [$currency->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Country Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="country" class="form-control" value="{{ $currency->country }}" placeholder="{{ translate('Country') }}" required>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Currency Code (ISO)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="currency_code" class="form-control text-uppercase" value="{{ $currency->currency_code }}" maxlength="10" required>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Currency Symbol') }} <span class="text-danger">*</span></label>
                        <input type="text" name="currency_symbol" class="form-control" value="{{ $currency->currency_symbol }}" required>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Symbol Position') }}</label>
                        <select name="symbol_position" class="form-control">
                            <option value="right" {{ ($currency->symbol_position ?? 'right') == 'right' ? 'selected' : '' }}>{{ translate('Right (100 ﷼)') }}</option>
                            <option value="left" {{ ($currency->symbol_position ?? 'right') == 'left' ? 'selected' : '' }}>{{ translate('Left ($100)') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">{{ translate('Decimal Digits') }}</label>
                        <input type="number" name="decimal_digits" class="form-control" value="{{ $currency->decimal_digits ?? 2 }}" min="0" max="6">
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="input-label">
                            {{ translate('Exchange Rate (in Base Units)') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.000001" min="0.000001" name="exchange_rate" class="form-control" value="{{ $currency->exchange_rate }}" {{ $currency->is_base ? 'readonly' : '' }} required>
                        @if($currency->is_base)
                            <small class="text-success font-weight-bold">{{ translate('This is the Base Currency (Anchor Rate = 1.0)') }}</small>
                        @else
                            <small class="text-muted">{{ translate('1 unit of this currency = How many units of ') }} {{ $baseCurrency->currency_code ?? 'Base' }}?</small>
                        @endif
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.business-settings.currency-add') }}" class="btn btn-secondary mr-2">
                            {{ translate('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="tio-save"></i> {{ translate('Update Currency') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
