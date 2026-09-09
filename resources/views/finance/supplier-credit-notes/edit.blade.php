@extends('layouts.erp')

@section('title', 'Edit Supplier Credit Note')

@section('content_header')
    <h1>Edit Supplier Credit Note</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.supplier-credit-notes.update', $creditNote->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="credit_note_number">Credit Note Number</label>
                            <input type="text" class="form-control" name="credit_note_number" value="{{ old('credit_note_number', $creditNote->credit_note_number) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="credit_note_date">Credit Note Date</label>
                            <input type="date" class="form-control" name="credit_note_date" value="{{ old('credit_note_date', $creditNote->credit_note_date->format('Y-m-d')) }}" required>
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
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $creditNote->supplier_id) == $supplier->id)>
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
                                    <option value="{{ $invoice->id }}" @selected(old('supplier_invoice_id', $creditNote->supplier_invoice_id) == $invoice->id)>
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
                            <input type="number" class="form-control" name="subtotal" step="0.01" min="0" value="{{ old('subtotal', $creditNote->subtotal) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tax_amount">Tax Amount</label>
                            <input type="number" class="form-control" name="tax_amount" step="0.01" min="0" value="{{ old('tax_amount', $creditNote->tax_amount) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" name="total_amount" step="0.01" min="0" value="{{ old('total_amount', $creditNote->total_amount) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason</label>
                    <textarea class="form-control" name="reason" rows="3">{{ old('reason', $creditNote->reason) }}</textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Credit Note</button>
                    <a href="{{ route('finance.supplier-credit-notes.show', $creditNote->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
