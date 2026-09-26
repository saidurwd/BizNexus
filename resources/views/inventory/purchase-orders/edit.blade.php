@extends('layouts.erp')

@section('title', __('Edit purchase order :number', ['number' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Edit purchase order :number', ['number' => $order->order_number]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.purchase-orders.update', $order->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('inventory.purchase-orders._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
