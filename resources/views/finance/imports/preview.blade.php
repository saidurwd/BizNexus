@extends('layouts.erp')

@section('title', __('Review import'))

@section('content_header')
    <h1>{{ __('Review import: :type', ['type' => $importer->label()]) }}</h1>
@endsection

@php $columns = array_keys($importer->columns()); $valid = $invalidCount === 0 && $fileErrors === []; @endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <div><strong>{{ count($rows) }}</strong> {{ __('rows') }}</div>
            @foreach ($actions as $action => $count)
                <div><strong>{{ $count }}</strong> {{ __('to :action', ['action' => __($action)]) }}</div>
            @endforeach
            <div class="{{ $invalidCount ? 'text-danger' : 'text-success' }}"><strong>{{ $invalidCount }}</strong> {{ __('with problems') }}</div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('finance.imports.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
                @if ($valid)
                    <form method="POST" action="{{ route('finance.imports.confirm.'.$type, $token) }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $options['date'] }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> {{ trans_choice('Import :count row|Import :count rows', count($rows)) }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if ($fileErrors)
        <div class="alert alert-danger">@foreach ($fileErrors as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    @unless ($valid)
        <div class="alert alert-warning">{{ __('Nothing has been imported. Correct the rows marked below in your file and upload it again.') }}</div>
    @endunless

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Line') }}</th>
                        <th>{{ __('Result') }}</th>
                        @foreach ($columns as $column)<th>{{ $column }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($rows, 0, 1000) as $row)
                        <tr class="{{ $row['errors'] ? 'table-danger' : '' }}">
                            <td>{{ $row['line'] }}</td>
                            <td>
                                @if ($row['errors'])
                                    <span class="text-danger small">{{ implode(' ', $row['errors']) }}</span>
                                @else
                                    <span class="badge text-bg-{{ $row['action'] === 'update' ? 'info' : 'success' }}">{{ __(ucfirst($row['action'])) }}</span>
                                @endif
                            </td>
                            @foreach ($columns as $column)<td class="small">{{ $row['data'][$column] ?? '' }}</td>@endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (count($rows) > 1000)
            <div class="card-footer small text-body-secondary">{{ __('Showing the first 1,000 rows.') }}</div>
        @endif
    </div>
@endsection
