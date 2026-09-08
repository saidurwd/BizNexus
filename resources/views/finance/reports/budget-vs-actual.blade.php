@extends('layouts.erp')

@section('title', 'Budget vs Actual')

@section('content_header')
    <h1>Budget vs Actual</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Budget vs Actual Report</h3>
            <div class="card-tools">
                <select class="form-control form-control-sm">
                    <option>Select Budget</option>
                </select>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Budget</th>
                        <th class="text-right">Actual</th>
                        <th class="text-right">Variance</th>
                        <th class="text-right">Variance %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center">Select a budget to view comparison</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
