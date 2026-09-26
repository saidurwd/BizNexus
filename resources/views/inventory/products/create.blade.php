@extends('layouts.erp')

@section('title', __('New product'))

@section('content_header')
    <h1>{{ __('New product') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.products.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('inventory.products._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('inventory.products.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
