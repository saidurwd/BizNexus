@extends('layouts.erp')

@section('title', 'Edit Tax Code')

@section('content_header')
    <h1>Edit Tax Code</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.taxes.update', $tax->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('finance.taxes._form', ['tax' => $tax])

                <p class="text-muted">Current rate: {{ rtrim(rtrim(number_format((float) $tax->rate, 4), '0'), '.') }}%. Record rate changes on the tax code page so earlier documents keep their rate.</p>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Tax Code</button>
                    <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
