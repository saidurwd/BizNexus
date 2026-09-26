@extends('layouts.erp')

@section('title', __('New quotation'))

@section('content_header')
    <h1>{{ __('New quotation') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('sales.quotations.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('sales.quotations._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('sales.quotations.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
