@extends('layouts.erp')

@section('title', __('Tax Rules'))

@section('content_header')
    <h1>{{ __('Tax Rules') }}</h1>
    <p class="text-muted mb-0">{{ __('Invoice lines without a tax code get one from the most specific matching rule (country, then customer/supplier type, then supply type); ties go to the lowest priority number. A counterparty with a tax registration number is B2B.') }}</p>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr><th>{{ __('Direction') }}</th><th>{{ __('Country') }}</th><th>{{ __('Counterparty') }}</th><th>{{ __('Supply') }}</th><th>{{ __('Tax code') }}</th><th>{{ __('Reverse charge') }}</th><th>{{ __('Priority') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>{{ ucfirst($rule->direction) }}</td>
                            <td>{{ $rule->counterparty_country ?? 'Any' }}</td>
                            <td>{{ strtoupper($rule->counterparty_type) }}</td>
                            <td>{{ ucfirst($rule->supply_type) }}</td>
                            <td>{{ $rule->tax ? $rule->tax->tax_code : 'No tax' }}</td>
                            <td>{{ $rule->reverse_charge ? 'Yes' : 'No' }}</td>
                            <td>{{ $rule->priority }}</td>
                            <td class="text-end">
                                @can('finance.taxes.update')
                                    <form method="POST" action="{{ route('finance.tax-rules.destroy', $rule->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('No rules yet. Lines without a tax code are posted untaxed.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('finance.taxes.update')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add rule') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.tax-rules.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="direction">{{ __('Direction') }}</label>
                                <select class="form-control" id="direction" name="direction">
                                    <option value="sales">{{ __('Sales') }}</option>
                                    <option value="purchase">{{ __('Purchase') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <x-form.country-select name="counterparty_country" label="Counterparty country (empty = any)" />
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="counterparty_type">{{ __('Counterparty') }}</label>
                                <select class="form-control" id="counterparty_type" name="counterparty_type">
                                    <option value="any">{{ __('Any') }}</option>
                                    <option value="b2b">B2B</option>
                                    <option value="b2c">B2C</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="supply_type">{{ __('Supply') }}</label>
                                <select class="form-control" id="supply_type" name="supply_type">
                                    <option value="any">{{ __('Any') }}</option>
                                    <option value="goods">{{ __('Goods') }}</option>
                                    <option value="services">{{ __('Services') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="tax_id">{{ __('Tax code') }}</label>
                                <select class="form-control" id="tax_id" name="tax_id">
                                    <option value="">{{ __('No tax (exempt / out of scope)') }}</option>
                                    @foreach ($taxes as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->tax_code }} — {{ $tax->tax_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="reverse_charge">{{ __('Reverse charge') }}</label>
                                <select class="form-control" id="reverse_charge" name="reverse_charge">
                                    <option value="0">{{ __('No') }}</option>
                                    <option value="1">{{ __('Yes') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="priority">{{ __('Priority') }}</label>
                                <input type="number" class="form-control" id="priority" name="priority" min="1" max="999" value="100">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Add rule') }}</button>
                </form>
            </div>
        </div>
    @endcan
@endsection
