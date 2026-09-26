@extends('layouts.erp')

@section('title', __('Edit quotation :number', ['number' => $quotation->quotation_number]))

@section('content_header')
    <h1>{{ __('Edit quotation :number', ['number' => $quotation->quotation_number]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('sales.quotations.update', $quotation->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('sales.quotations._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('sales.quotations.show', $quotation->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
