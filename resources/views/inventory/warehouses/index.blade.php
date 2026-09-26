@extends('layouts.erp')

@section('title', __('Warehouses'))

@section('content_header')
    <h1>{{ __('Warehouses') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Places stock is kept. Receipts and issues use the default warehouse unless another is chosen.') }}</p>
@endsection

@php
    $form = function ($warehouse, string $action, string $method, string $submitLabel) use ($branches) {
        return view('inventory.warehouses._form', compact('warehouse', 'action', 'method', 'submitLabel', 'branches'));
    };
@endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('inventory.setup.manage')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td><code>{{ $warehouse->code }}</code> @if ($warehouse->is_default)<span class="badge text-bg-primary">{{ __('Default') }}</span>@endif</td>
                            <td>{{ $warehouse->name }}</td>
                            <td>{{ $warehouse->branch?->name ?? '—' }}</td>
                            <td class="small">{{ $warehouse->address ?? '—' }}</td>
                            <td><x-status-badge :status="$warehouse->status" /></td>
                            @can('inventory.setup.manage')
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-warehouse-{{ $warehouse->id }}">{{ __('Edit') }}</button>
                                    @unless ($warehouse->is_default)
                                        <form method="POST" action="{{ route('inventory.warehouses.destroy', $warehouse->id) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this warehouse?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    @endunless
                                </td>
                            @endcan
                        </tr>
                        @can('inventory.setup.manage')
                            <tr class="collapse" id="edit-warehouse-{{ $warehouse->id }}">
                                <td colspan="6" class="bg-body-tertiary">{{ $form($warehouse, route('inventory.warehouses.update', $warehouse->id), 'PUT', __('Save')) }}</td>
                            </tr>
                        @endcan
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No warehouses yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('inventory.setup.manage')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add a warehouse') }}</h3></div>
            <div class="card-body">{{ $form(null, route('inventory.warehouses.store'), 'POST', __('Add')) }}</div>
        </div>
    @endcan
@endsection
