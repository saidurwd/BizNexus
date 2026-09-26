@extends('layouts.erp')

@section('title', __('Edit adjustment :number', ['number' => $adjustment->adjustment_number]))

@section('content_header')
    <h1>{{ __('Edit adjustment :number', ['number' => $adjustment->adjustment_number]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.adjustments.update', $adjustment->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('inventory.adjustments._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('inventory.adjustments.show', $adjustment->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
