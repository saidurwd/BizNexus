@extends('layouts.erp')

@section('title', __('Asset Register'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Asset Register') }}</h1>
        @can('assets.manage')
            <div class="d-flex gap-2">
                <a href="{{ route('assets.capitalise.index') }}" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-down"></i> {{ __('Capitalise purchases') }}</a>
                <a href="{{ route('assets.assets.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('Register asset') }}</a>
            </div>
        @endcan
    </div>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">{{ __('Search') }}</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="{{ __('Number, name, serial, tag or custodian') }}">
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label">{{ __('Category') }}</label>
                <select id="category" name="category" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="branch" class="form-label">{{ __('Branch') }}</label>
                <select id="branch" name="branch" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('Not disposed') }}</option>
                    @foreach (\Modules\Assets\Models\FixedAsset::statusLabels() as $status => $label)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Location') }}</th>
                        <th>{{ __('In service') }}</th>
                        <th class="text-end">{{ __('Cost') }}</th>
                        <th class="text-end">{{ __('Accumulated depreciation') }}</th>
                        <th class="text-end">{{ __('Carrying amount') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td><a href="{{ route('assets.assets.show', $asset->id) }}">{{ $asset->asset_number }}</a></td>
                            <td>{{ $asset->name }} @if ($asset->tag)<small class="text-body-secondary">#{{ $asset->tag }}</small>@endif</td>
                            <td>{{ $asset->category?->name }}</td>
                            <td class="small">{{ collect([$asset->branch?->name, $asset->department?->name, $asset->location])->filter()->implode(' · ') ?: '—' }}</td>
                            <td>{{ Formatter::date($asset->in_service_date) }}</td>
                            <td class="text-end">{{ Formatter::amount($asset->cost) }}</td>
                            <td class="text-end">{{ Formatter::amount($asset->accumulated_depreciation) }}</td>
                            <td class="text-end fw-bold">{{ Formatter::amount($asset->bookValue()) }}</td>
                            <td><span class="badge text-bg-{{ ['ACTIVE' => 'primary', 'FULLY_DEPRECIATED' => 'secondary', 'DISPOSED' => 'dark'][$asset->status] ?? 'light' }}">{{ \Modules\Assets\Models\FixedAsset::statusLabels()[$asset->status] ?? $asset->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-body-secondary py-4">{{ __('No assets found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assets->hasPages())
            <div class="card-footer">{{ $assets->links() }}</div>
        @endif
    </div>
@endsection
