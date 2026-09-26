@extends('layouts.erp')

@section('title', __('Asset Categories'))

@section('content_header')
    <h1>{{ __('Asset Categories') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Classes of property, plant and equipment with their ledger accounts and default depreciation policy.') }}</p>
@endsection

@php
    $form = function ($category, string $action, string $method, string $submitLabel) use ($accounts) {
        return view('assets.categories._form', compact('category', 'action', 'method', 'submitLabel', 'accounts'));
    };
    $label = fn ($account) => $account ? $account->account_code.' — '.$account->account_name : '—';
@endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Depreciation') }}</th>
                        <th>{{ __('Asset account') }}</th>
                        <th>{{ __('Depreciation expense account') }}</th>
                        <th class="text-end">{{ __('Assets') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('assets.setup')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><code>{{ $category->code }}</code></td>
                            <td>{{ $category->name }}</td>
                            <td class="small">
                                {{ \Modules\Assets\Models\AssetCategory::methodLabels()[$category->depreciation_method] ?? $category->depreciation_method }}
                                · {{ trans_choice(':count month|:count months', $category->useful_life_months, ['count' => $category->useful_life_months]) }}
                                @if ($category->declining_rate) · {{ Formatter::percent($category->declining_rate) }}%@endif
                            </td>
                            <td class="small">{{ $label($category->assetAccount) }}</td>
                            <td class="small">{{ $label($category->depreciationExpenseAccount) }}</td>
                            <td class="text-end">{{ $category->assets_count }}</td>
                            <td><x-status-badge :status="$category->status" /></td>
                            @can('assets.setup')
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-category-{{ $category->id }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('assets.categories.destroy', $category->id) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this category?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                        @can('assets.setup')
                            <tr class="collapse" id="edit-category-{{ $category->id }}">
                                <td colspan="8" class="bg-body-tertiary">{{ $form($category, route('assets.categories.update', $category->id), 'PUT', __('Save')) }}</td>
                            </tr>
                        @endcan
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No categories yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('assets.setup')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add a category') }}</h3></div>
            <div class="card-body">{{ $form(null, route('assets.categories.store'), 'POST', __('Add')) }}</div>
        </div>
    @endcan
@endsection
