@extends('layouts.erp')

@section('title', __('Edit invoice :number', ['number' => $invoice->invoice_number]))

@section('content_header')
    <h1>{{ __('Edit invoice :number', ['number' => $invoice->invoice_number]) }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.customer-invoices.update', $invoice->id) }}" method="POST" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('finance.customer-invoices._form', ['invoice' => $invoice])
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.customer-invoices.show', $invoice->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
