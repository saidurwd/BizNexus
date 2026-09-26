@extends('layouts.erp')

@section('title', __('Edit :name', ['name' => $product->sku]))

@section('content_header')
    <h1>{{ __('Edit :name', ['name' => $product->sku.' — '.$product->name]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.products.update', $product->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('inventory.products._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('inventory.products.show', $product->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
