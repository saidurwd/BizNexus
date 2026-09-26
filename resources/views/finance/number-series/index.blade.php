@extends('layouts.erp')

@section('title', __('Number Series'))

@section('content_header')
    <h1>{{ __('Number Series') }}</h1>
    <p class="text-body-secondary mb-0">
        {{ __('How official document numbers look. Numbers are issued without gaps when a document is created (journals: when posted, restarting each fiscal year).') }}
        {{ __('Tokens: :tokens and {SEQUENCE:n} (the counter, padded to n digits).', ['tokens' => implode(' ', $tokens)]) }}
    </p>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Document') }}</th>
                        <th style="width: 130px">{{ __('Prefix') }}</th>
                        <th style="width: 280px">{{ __('Format') }}</th>
                        <th style="width: 140px">{{ __('Next number') }}</th>
                        <th>{{ __('Next document will be') }}</th>
                        @can('finance.number-series.manage')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($series as $row)
                        <tr>
                            @can('finance.number-series.manage')
                                <td>
                                    {{ __($row['label']) }}
                                    @if ($row['per_fiscal_year'])
                                        <div class="small text-body-secondary">{{ __('Counter restarts each fiscal year') }}</div>
                                    @endif
                                    <form id="series-{{ $row['code'] }}" method="POST" action="{{ route('finance.number-series.update', $row['code']) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                </td>
                                <td><input form="series-{{ $row['code'] }}" type="text" name="prefix" value="{{ $row['prefix'] }}" class="form-control form-control-sm" maxlength="20" aria-label="{{ __('Prefix') }}"></td>
                                <td><input form="series-{{ $row['code'] }}" type="text" name="format" value="{{ $row['format'] }}" class="form-control form-control-sm font-monospace" maxlength="60" required aria-label="{{ __('Format') }}"></td>
                                <td><input form="series-{{ $row['code'] }}" type="number" name="next_number" value="{{ $row['next_number'] }}" min="{{ $row['next_number'] }}" class="form-control form-control-sm" required aria-label="{{ __('Next number') }}"></td>
                                <td><code>{{ $row['preview'] }}</code></td>
                                <td><button form="series-{{ $row['code'] }}" type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button></td>
                            @else
                                <td>{{ __($row['label']) }}</td>
                                <td>{{ $row['prefix'] }}</td>
                                <td><code>{{ $row['format'] }}</code></td>
                                <td>{{ $row['next_number'] }}</td>
                                <td><code>{{ $row['preview'] }}</code></td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
@endsection
