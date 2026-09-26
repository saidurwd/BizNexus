@extends('layouts.erp')

@section('title', __('Add Tax Code'))

@section('content_header')
    <h1>{{ __('Add Tax Code') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.taxes.store') }}" method="POST">
                @csrf

                @include('finance.taxes._form')

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Tax Code') }}</button>
                    <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
