@extends('layouts.erp')

@section('title', __('New purchase order'))

@section('content_header')
    <h1>{{ __('New purchase order') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.purchase-orders.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('inventory.purchase-orders._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
