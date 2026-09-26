@extends('layouts.erp')

@section('title', __('Edit credit note :number', ['number' => $creditNote->note_number]))

@section('content_header')
    <h1>{{ __('Edit credit note :number', ['number' => $creditNote->note_number]) }}</h1>
@endsection

@section('content')
    <form action="{{ route('finance.customer-credit-notes.update', $creditNote->id) }}" method="POST" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('finance.customer-credit-notes._form', ['creditNote' => $creditNote])
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
            <a href="{{ route('finance.customer-credit-notes.show', $creditNote->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
