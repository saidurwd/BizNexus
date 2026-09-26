@extends('layouts.erp')

@section('title', __('New customer credit note'))

@section('content_header')
    <h1>{{ __('New customer credit note') }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.customer-credit-notes.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            @include('finance.customer-credit-notes._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.customer-credit-notes.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
