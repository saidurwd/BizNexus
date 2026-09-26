@extends('layouts.erp')

@section('title', 'Add Tax Code')

@section('content_header')
    <h1>Add Tax Code</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.taxes.store') }}" method="POST">
                @csrf

                @include('finance.taxes._form')

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Tax Code</button>
                    <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
