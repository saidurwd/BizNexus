@extends('layouts.erp')

@section('title', __('Edit :name', ['name' => $party->name]))

@section('content_header')
    <h1>{{ __('Edit :name', ['name' => $party->name]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('finance.customers.update', $party->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('finance.parties._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('finance.customers.show', $party->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
