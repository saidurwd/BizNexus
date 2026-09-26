@extends('layouts.erp')

@section('title', __('Management Reports'))

@section('content_header')
    <h1>{{ __('Management Reports') }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-report-letterhead title="{{ __('Management Reports') }}" />

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Budget vs Actual') }}</h3>
                </div>
                <div class="card-body">
                    <p>{{ __('Compare budgeted amounts against actual spending.') }}</p>
                    <a href="{{ route('finance.budget-vs-actual') }}" class="btn btn-primary">{{ __('View Report') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Expense Analysis') }}</h3>
                </div>
                <div class="card-body">
                    <p>{{ __('Analyze expenses by category and department.') }}</p>
                    <button class="btn btn-primary" disabled>{{ __('Coming Soon') }}</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Revenue Analysis') }}</h3>
                </div>
                <div class="card-body">
                    <p>{{ __('Analyze revenue streams and trends.') }}</p>
                    <button class="btn btn-primary" disabled>{{ __('Coming Soon') }}</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Cost Center Analysis') }}</h3>
                </div>
                <div class="card-body">
                    <p>{{ __('Analyze performance by cost center.') }}</p>
                    <button class="btn btn-primary" disabled>{{ __('Coming Soon') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
