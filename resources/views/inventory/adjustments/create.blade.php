@extends('layouts.erp')

@section('title', $reason === 'count' ? __('New stock count') : __('New adjustment'))

@section('content_header')
    <h1>{{ $reason === 'count' ? __('New stock count') : __('New adjustment') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="card">
        @csrf
        <div class="card-body">
            @include('inventory.adjustments._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
