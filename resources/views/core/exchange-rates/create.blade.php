@extends('layouts.erp')

@section('title', __('Add Exchange Rate'))

@section('content_header')
    <h1>{{ __('Add Exchange Rate') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.exchange-rates.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="currency_id">{{ __('Currency') }}</label>
                            <select name="currency_id" class="form-control" required>
                                <option value="">{{ __('Select Currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>
                                        {{ $currency->code }} — {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rate_date">{{ __('Rate Date') }}</label>
                            <input type="date" class="form-control" name="rate_date" value="{{ old('rate_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rate_type">{{ __('Rate Type') }}</label>
                    <select class="form-control" name="rate_type" id="rate_type" required>
                        @foreach (\Modules\Finance\Enums\ExchangeRateType::cases() as $rateType)
                            <option value="{{ $rateType->value }}" @selected(old('rate_type', 'spot') === $rateType->value)>{{ ucfirst($rateType->value) }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">{{ __('Spot: transactions. Average: income statement translation. Closing: period-end revaluation.') }}</small>
                    @error('rate_type')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    @error('rate_date')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="exchange_rate">{{ __('Exchange Rate') }}</label>
                            <input type="number" step="0.0001" class="form-control" name="exchange_rate" value="{{ old('exchange_rate') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="source">{{ __('Source') }}</label>
                            <input type="text" class="form-control" name="source" value="{{ old('source') }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Exchange Rate') }}</button>
                    <a href="{{ route('core.exchange-rates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
