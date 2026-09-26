@extends('layouts.erp')

@section('title', __('New customer invoice'))

@section('content_header')
    <h1>{{ __('New customer invoice') }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.customer-invoices.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            @include('finance.customer-invoices._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.customer-invoices.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
