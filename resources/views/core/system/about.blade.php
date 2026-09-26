@extends('layouts.erp')

@section('title', __('About'))

@section('content_header')
    <h1>{{ __('About :name', ['name' => config('app.name')]) }}</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Application') }}</h3></div>
                <table class="table table-sm mb-0">
                    @foreach ($application as $label => $value)
                        <tr><th class="w-50">{{ $label }}</th><td>{{ $value }}</td></tr>
                    @endforeach
                </table>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Modules') }}</h3></div>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>{{ __('Module') }}</th><th class="text-end">{{ __('Screens and endpoints') }}</th><th class="text-end">{{ __('Models') }}</th></tr></thead>
                    @foreach ($modules as $module)
                        <tr><td>{{ $module['name'] }}</td><td class="text-end">{{ $module['routes'] }}</td><td class="text-end">{{ $module['models'] }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Platform') }}</h3></div>
                <table class="table table-sm mb-0">
                    @foreach ($platform as $label => $value)
                        <tr><th class="w-50">{{ $label }}</th><td>{{ $value }}</td></tr>
                    @endforeach
                    <tr><th>{{ __('Audit log entries') }}</th><td>{{ Formatter::number($auditEntries, 0) }}</td></tr>
                </table>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Key packages') }}</h3></div>
                <table class="table table-sm mb-0">
                    @foreach ($packages as $package => $version)
                        <tr><th class="w-50"><code>{{ $package }}</code></th><td>{{ $version }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
