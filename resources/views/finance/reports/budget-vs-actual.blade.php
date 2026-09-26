@extends('layouts.erp')

@section('title', __('Budget vs Actual'))

@section('content_header')
    <h1>{{ __('Budget vs Actual') }}</h1>
@endsection

@section('content')
    <x-report-letterhead title="{{ __('Budget vs Actual') }}" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Budget vs Actual Report') }}</h3>
            <div class="card-tools">
                <select class="form-control form-control-sm">
                    <option>{{ __('Select Budget') }}</option>
                </select>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Account') }}</th>
                        <th class="text-end">{{ __('Budget') }}</th>
                        <th class="text-end">{{ __('Actual') }}</th>
                        <th class="text-end">{{ __('Variance') }}</th>
                        <th class="text-end">{{ __('Variance %') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center">{{ __('Select a budget to view comparison') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
