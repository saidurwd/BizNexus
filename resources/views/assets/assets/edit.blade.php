@extends('layouts.erp')

@section('title', __('Edit :name', ['name' => $asset->asset_number]))

@section('content_header')
    <h1>{{ __('Edit :name', ['name' => $asset->asset_number.' — '.$asset->name]) }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('assets.assets.update', $asset->id) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">@include('assets.assets._form')</div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('assets.assets.show', $asset->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
