@extends('layouts.erp')

@section('title', __('Data Import'))

@section('content_header')
    <h1>{{ __('Data Import') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Bring data from spreadsheets or another system. Download the template, fill it in, upload it, and review every row before anything is saved.') }}</p>
@endsection

@section('content')
    <div class="row">
        @foreach ($types as $type)
            @can($type['permission'])
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">{{ $type['importer']->label() }}</h3>
                            <a href="{{ route('finance.imports.template.'.$type['key']) }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-download"></i> {{ __('Template (CSV)') }}</a>
                        </div>
                        <div class="card-body">
                            <details class="mb-3">
                                <summary class="small">{{ __('Columns') }}</summary>
                                <ul class="small mb-0 mt-2">
                                    @foreach ($type['importer']->columns() as $column => [$required, $hint])
                                        <li><code>{{ $column }}</code>@if ($required) <strong>*</strong>@endif @if ($hint) — {{ $hint }}@endif</li>
                                    @endforeach
                                </ul>
                            </details>
                            <form method="POST" action="{{ route('finance.imports.preview.'.$type['key']) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                                @csrf
                                <div class="col">
                                    <label for="file-{{ $type['key'] }}" class="form-label">{{ __('CSV or Excel file') }}</label>
                                    <input type="file" id="file-{{ $type['key'] }}" name="file" class="form-control" accept=".csv,.xlsx,text/csv" required>
                                </div>
                                @if ($type['key'] === 'opening-balances')
                                    <div class="col-md-4">
                                        <label for="date" class="form-label">{{ __('Balances as of') }}</label>
                                        <input type="date" id="date" name="date" class="form-control" required>
                                    </div>
                                @endif
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">{{ __('Check file') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endforeach
    </div>
@endsection
