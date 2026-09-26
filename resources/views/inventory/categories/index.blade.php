@extends('layouts.erp')

@section('title', __('Product Categories'))

@section('content_header')
    <h1>{{ __('Product Categories') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Group products and give them their default ledger accounts. A product\'s own account overrides its category\'s.') }}</p>
@endsection

@php
    $form = function ($category, string $action, string $method, string $submitLabel) use ($accounts) {
        return view('inventory.categories._form', compact('category', 'action', 'method', 'submitLabel', 'accounts'));
    };
    $accountLabel = fn ($account) => $account ? $account->account_code.' — '.$account->account_name : '—';
@endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Inventory account') }}</th>
                        <th>{{ __('Cost of goods sold account') }}</th>
                        <th>{{ __('Revenue account') }}</th>
                        <th class="text-end">{{ __('Products') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('inventory.setup.manage')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><code>{{ $category->code }}</code></td>
                            <td>{{ $category->name }}</td>
                            <td class="small">{{ $accountLabel($category->inventoryAccount) }}</td>
                            <td class="small">{{ $accountLabel($category->cogsAccount) }}</td>
                            <td class="small">{{ $accountLabel($category->revenueAccount) }}</td>
                            <td class="text-end">{{ $category->products_count }}</td>
                            <td><x-status-badge :status="$category->status" /></td>
                            @can('inventory.setup.manage')
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-category-{{ $category->id }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('inventory.categories.destroy', $category->id) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this category?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                        @can('inventory.setup.manage')
                            <tr class="collapse" id="edit-category-{{ $category->id }}">
                                <td colspan="8" class="bg-body-tertiary">{{ $form($category, route('inventory.categories.update', $category->id), 'PUT', __('Save')) }}</td>
                            </tr>
                        @endcan
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No categories yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('inventory.setup.manage')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add a category') }}</h3></div>
            <div class="card-body">{{ $form(null, route('inventory.categories.store'), 'POST', __('Add')) }}</div>
        </div>
    @endcan
@endsection
