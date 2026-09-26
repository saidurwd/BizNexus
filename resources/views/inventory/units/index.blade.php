@extends('layouts.erp')

@section('title', __('Units of Measure'))

@section('content_header')
    <h1>{{ __('Units of Measure') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('The units products are bought, stored and sold in. Decimals is how many decimal places a quantity may have.') }}</p>
@endsection

@php
    $form = function ($unit, string $action, string $method, string $submitLabel) {
        return view('inventory.units._form', compact('unit', 'action', 'method', 'submitLabel'));
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
                        <th class="text-end">{{ __('Decimals') }}</th>
                        <th class="text-end">{{ __('Products') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('inventory.setup.manage')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td><code>{{ $unit->code }}</code></td>
                            <td>{{ $unit->name }}</td>
                            <td class="text-end">{{ $unit->decimals }}</td>
                            <td class="text-end">{{ $unit->products_count }}</td>
                            <td><x-status-badge :status="$unit->status" /></td>
                            @can('inventory.setup.manage')
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-unit-{{ $unit->id }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('inventory.units.destroy', $unit->id) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this unit?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                        @can('inventory.setup.manage')
                            <tr class="collapse" id="edit-unit-{{ $unit->id }}">
                                <td colspan="6" class="bg-body-tertiary">{{ $form($unit, route('inventory.units.update', $unit->id), 'PUT', __('Save')) }}</td>
                            </tr>
                        @endcan
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No units yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('inventory.setup.manage')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add a unit') }}</h3></div>
            <div class="card-body">{{ $form(null, route('inventory.units.store'), 'POST', __('Add')) }}</div>
        </div>
    @endcan
@endsection
