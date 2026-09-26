@extends('layouts.erp')

@section('title', __('New customer'))

@section('content_header')
    <h1>{{ __('New customer') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('finance.customers.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('finance.parties._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('finance.customers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
