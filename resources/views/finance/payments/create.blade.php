@extends('layouts.erp')

@section('title', __('Create Payment'))

@section('content_header')
    <h1>{{ __('Create Payment') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.payments.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_date">{{ __('Payment Date') }}</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_method">{{ __('Payment Method') }}</label>
                            <select class="form-control" name="payment_method" required>
                                <option value="BANK_TRANSFER">{{ __('Bank Transfer') }}</option>
                                <option value="CASH">{{ __('Cash') }}</option>
                                <option value="CHECK">{{ __('Check') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="supplier_id">{{ __('Supplier') }}</label>
                            <select class="form-control" name="supplier_id" required>
                                <option value="">{{ __('Select Supplier') }}</option>
                                @foreach(\Modules\Finance\Models\Supplier::where('status', 'active')->get() as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->supplier_code }} — {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount">{{ __('Amount') }}</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                </div>

                @if ($withholdingTaxes->isNotEmpty())
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="withholding_tax_id">{{ __('Withholding tax') }}</label>
                                <select class="form-control" id="withholding_tax_id" name="withholding_tax_id">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($withholdingTaxes as $withholdingTax)
                                        <option value="{{ $withholdingTax->id }}" @selected(old('withholding_tax_id') == $withholdingTax->id)>{{ $withholdingTax->tax_code }} — {{ $withholdingTax->tax_name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ __('Deducted from the amount paid to the supplier and owed to the tax authority.') }}</small>
                                @error('withholding_tax_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_account_id">{{ __('Bank Account') }}</label>
                            <select class="form-control" name="bank_account_id">
                                <option value="">— None —</option>
                                @foreach(\Modules\Finance\Models\BankAccount::where('status', 'active')->get() as $account)
                                    <option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>
                                        {{ $account->bank_name }} — {{ $account->display_account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reference">{{ __('Reference') }}</label>
                            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Create Payment') }}</button>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
