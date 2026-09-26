@extends('layouts.erp')

@section('title', __('Register asset'))

@section('content_header')
    <h1>{{ __('Register asset') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('assets.assets.store') }}" class="card">
        @csrf
        <div class="card-body">@include('assets.assets._form')</div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Register') }}</button>
            <a href="{{ route('assets.assets.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
