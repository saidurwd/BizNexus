@extends('layouts.erp')

@section('title', 'Create Payment')

@section('content_header')
    <h1>Create Payment</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.payments.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_date">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select class="form-control" name="payment_method" required>
                                <option value="BANK_TRANSFER">Bank Transfer</option>
                                <option value="CASH">Cash</option>
                                <option value="CHECK">Check</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_id">Supplier</label>
                            <select class="form-control" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                @foreach(\Modules\Finance\Models\Supplier::where('status', 'active')->get() as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->supplier_code }} — {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                </div>

                @if ($withholdingTaxes->isNotEmpty())
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="withholding_tax_id">Withholding tax</label>
                                <select class="form-control" id="withholding_tax_id" name="withholding_tax_id">
                                    <option value="">None</option>
                                    @foreach ($withholdingTaxes as $withholdingTax)
                                        <option value="{{ $withholdingTax->id }}" @selected(old('withholding_tax_id') == $withholdingTax->id)>{{ $withholdingTax->tax_code }} — {{ $withholdingTax->tax_name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Deducted from the amount paid to the supplier and owed to the tax authority.</small>
                                @error('withholding_tax_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account_id">Bank Account</label>
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
                        <div class="form-group">
                            <label for="reference">Reference</label>
                            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Payment</button>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
