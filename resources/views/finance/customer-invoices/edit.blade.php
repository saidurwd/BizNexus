@extends('layouts.erp')

@section('title', 'Edit Customer Invoice')

@section('content_header')
    <h1>Edit Customer Invoice</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.customer-invoices.update', $invoice->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number</label>
                            <input type="text" class="form-control" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="invoice_date">Invoice Date</label>
                            <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="customer_id">Customer</label>
                            <select class="form-control" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice->customer_id) == $customer->id)>
                                        {{ $customer->customer_code ?? $customer->id }} — {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_id">Tax</label>
                            <select class="form-control" name="tax_id">
                                <option value="">No Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}" @selected(old('tax_id', $invoice->tax_id) == $tax->id)>
                                        {{ $tax->tax_code }} — {{ $tax->tax_name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subtotal">Subtotal</label>
                            <input type="number" class="form-control" name="subtotal" step="0.01" min="0" value="{{ old('subtotal', $invoice->subtotal) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" name="total_amount" step="0.01" min="0" value="{{ old('total_amount', $invoice->total_amount) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $invoice->description) }}</textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                    <a href="{{ route('finance.customer-invoices.show', $invoice->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
