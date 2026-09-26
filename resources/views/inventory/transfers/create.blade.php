@extends('layouts.erp')

@php
    $lines = old('lines', [[]]);
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
@endphp

@section('title', __('New transfer'))

@section('content_header')
    <h1>{{ __('New transfer') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Moves stock between warehouses at once. The value does not change, so nothing is posted to the ledger.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.transfers.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="from_warehouse_id" class="form-label">{{ __('From warehouse') }}</label>
                    <select id="from_warehouse_id" name="from_warehouse_id" class="form-select" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('from_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="to_warehouse_id" class="form-label">{{ __('To warehouse') }}</label>
                    <select id="to_warehouse_id" name="to_warehouse_id" class="form-select @error('to_warehouse_id') is-invalid @enderror" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('to_warehouse_id', $warehouses->get(1)?->id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="transfer_date" class="form-label">{{ __('Date') }}</label>
                    <input type="date" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', $today) }}" class="form-control" required>
                </div>
            </div>

            <div data-repeater>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 260px">{{ __('Product') }}</th>
                                <th style="width: 160px" class="text-end">{{ __('Quantity') }}</th>
                                <th style="width: 60px"></th>
                                <th style="width: 40px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                            </tr>
                        </thead>
                        <tbody data-rows>
                            @foreach (array_values($lines) as $index => $line)
                                @include('inventory.transfers._line', ['index' => $index, 'line' => $line])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-row><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button>
                <template data-row-template>
                    @include('inventory.transfers._line', ['index' => '__INDEX__', 'line' => []])
                </template>
            </div>

            <div class="mt-3">
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Transfer') }}</button>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
    <x-inventory.line-repeater-script />
@endsection
