@extends('layouts.erp')

@section('title', 'Create Supplier Credit Note')

@section('content_header')
    <h1>Create Supplier Credit Note</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.supplier-credit-notes.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="credit_note_number">Credit Note Number</label>
                            <input type="text" class="form-control" name="credit_note_number" value="{{ old('credit_note_number') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="credit_note_date">Credit Note Date</label>
                            <input type="date" class="form-control" name="credit_note_date" value="{{ old('credit_note_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_id">Supplier</label>
                            <select class="form-control" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->supplier_code }} — {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_invoice_id">Related Invoice</label>
                            <select class="form-control" name="supplier_invoice_id">
                                <option value="">None</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" @selected(old('supplier_invoice_id') == $invoice->id)>
                                        {{ $invoice->invoice_number }} ({{ number_format($invoice->total_amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subtotal">Subtotal</label>
                            <input type="number" class="form-control" name="subtotal" step="0.01" min="0" value="{{ old('subtotal') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tax_amount">Tax Amount</label>
                            <input type="number" class="form-control" name="tax_amount" step="0.01" min="0" value="{{ old('tax_amount', 0) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" name="total_amount" step="0.01" min="0" value="{{ old('total_amount') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason</label>
                    <textarea class="form-control" name="reason" rows="3">{{ old('reason') }}</textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Create Credit Note</button>
                    <a href="{{ route('finance.supplier-credit-notes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
