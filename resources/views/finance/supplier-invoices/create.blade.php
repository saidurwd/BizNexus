@extends('layouts.erp')

@section('title', __('Record supplier invoice'))

@section('content_header')
    <h1>{{ __('Record supplier invoice') }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.supplier-invoices.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            @include('finance.supplier-invoices._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.supplier-invoices.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
