@extends('layouts.erp')

@section('title', 'Management Reports')

@section('content_header')
    <h1>Management Reports</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Budget vs Actual</h3>
                </div>
                <div class="card-body">
                    <p>Compare budgeted amounts against actual spending.</p>
                    <a href="{{ route('finance.budget-vs-actual') }}" class="btn btn-primary">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Expense Analysis</h3>
                </div>
                <div class="card-body">
                    <p>Analyze expenses by category and department.</p>
                    <button class="btn btn-primary" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Revenue Analysis</h3>
                </div>
                <div class="card-body">
                    <p>Analyze revenue streams and trends.</p>
                    <button class="btn btn-primary" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cost Center Analysis</h3>
                </div>
                <div class="card-body">
                    <p>Analyze performance by cost center.</p>
                    <button class="btn btn-primary" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
    </div>
@endsection
