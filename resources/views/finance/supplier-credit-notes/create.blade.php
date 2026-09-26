@extends('layouts.erp')

@section('title', __('New supplier credit note'))

@section('content_header')
    <h1>{{ __('New supplier credit note') }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.supplier-credit-notes.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            @include('finance.supplier-credit-notes._form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.supplier-credit-notes.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
