@extends('layouts.erp')

@section('title', __('Capitalise Purchases'))

@section('content_header')
    <h1>{{ __('Capitalise Purchases') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Supplier invoice and goods receipt lines posted to an asset category\'s asset account that are not in the register yet. Registering them adds no journal: the purchase already debited the asset account.') }}</p>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Document') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Cost') }}</th>
                        <th style="min-width: 520px">{{ __('Register as') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $line)
                        <tr>
                            <td>{{ Formatter::date(\Carbon\CarbonImmutable::parse($line['date'])) }}</td>
                            <td>{{ $line['reference'] }}</td>
                            <td>{{ $line['supplier'] ?? '—' }}</td>
                            <td>{{ $line['description'] }} @if (bccomp($line['quantity'], '1', 4) !== 0)<small class="text-body-secondary">× {{ rtrim(rtrim($line['quantity'], '0'), '.') }}</small>@endif</td>
                            <td class="text-end">{{ Formatter::amount($line['cost']) }}</td>
                            <td>
                                <form method="POST" action="{{ route('assets.capitalise.store') }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="source_type" value="{{ $line['source_type'] }}">
                                    <input type="hidden" name="source_id" value="{{ $line['source_id'] }}">
                                    <select name="category_id" class="form-select form-select-sm" required aria-label="{{ __('Category') }}">
                                        <option value="">{{ __('Select category') }}</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="name" value="{{ $line['description'] }}" class="form-control form-control-sm" maxlength="255" aria-label="{{ __('Name') }}">
                                    <input type="date" name="in_service_date" value="{{ $line['date'] }}" class="form-control form-control-sm" aria-label="{{ __('In service from') }}">
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">{{ __('Register') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('Nothing to capitalise. Purchases appear here once they are posted to an asset category\'s asset account.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
