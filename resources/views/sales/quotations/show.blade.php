@extends('layouts.erp')

@php $currencyCode = $quotation->currencyCode(); @endphp

@section('title', __('Quotation :number', ['number' => $quotation->quotation_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Quotation :number', ['number' => $quotation->quotation_number]) }}</h1>
        <x-status-badge :status="$quotation->status" class="fs-6" />
        @if ($quotation->isExpired())<span class="badge text-bg-warning fs-6">{{ __('Expired') }}</span>@endif
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Customer') }}</dt>
                <dd class="col-sm-9"><a href="{{ route('finance.customers.show', $quotation->customer_id) }}">{{ $quotation->customer?->name }}</a></dd>
                <dt class="col-sm-3">{{ __('Customer reference') }}</dt>
                <dd class="col-sm-9">{{ $quotation->customer_reference ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Quotation date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($quotation->quotation_date) }}</dd>
                <dt class="col-sm-3">{{ __('Valid until') }}</dt>
                <dd class="col-sm-9">{{ $quotation->valid_until ? Formatter::date($quotation->valid_until) : '—' }}</dd>
                <dt class="col-sm-3">{{ __('Created by') }}</dt>
                <dd class="col-sm-9">{{ $quotation->createdBy?->name ?? '—' }}</dd>
                @if ($quotation->salesOrder)
                    <dt class="col-sm-3">{{ __('Sales order') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('sales.orders.show', $quotation->salesOrder->id) }}">{{ $quotation->salesOrder->order_number }}</a></dd>
                @endif
                @if ($quotation->notes)
                    <dt class="col-sm-3">{{ __('Notes') }}</dt>
                    <dd class="col-sm-9">{!! nl2br(e($quotation->notes)) !!}</dd>
                @endif
            </dl>
        </div>
    </div>

    @include('sales._document-lines', ['document' => $quotation, 'progress' => false])

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('sales.quotations.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        <a href="{{ route('sales.quotations.pdf', $quotation->id) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-filetype-pdf"></i> {{ __('PDF') }}</a>

        @can('sales.quotations.manage')
            @if ($quotation->isEditable())
                <a href="{{ route('sales.quotations.edit', $quotation->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
            @endif
            @if ($quotation->status === 'DRAFT')
                <form method="POST" action="{{ route('sales.quotations.send', $quotation->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-send"></i> {{ __('Mark as sent') }}</button>
                </form>
                <form method="POST" action="{{ route('sales.quotations.destroy', $quotation->id) }}" onsubmit="return confirm(@js(__('Delete this quotation?')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endif
            @if (in_array($quotation->status, ['DRAFT', 'SENT'], true))
                <form method="POST" action="{{ route('sales.quotations.accept', $quotation->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-hand-thumbs-up"></i> {{ __('Customer accepted') }}</button>
                </form>
            @endif
            @if (in_array($quotation->status, ['DRAFT', 'SENT', 'ACCEPTED'], true))
                <form method="POST" action="{{ route('sales.quotations.decline', $quotation->id) }}" onsubmit="return confirm(@js(__('Mark this quotation as declined?')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-hand-thumbs-down"></i> {{ __('Customer declined') }}</button>
                </form>
            @endif
        @endcan
    </div>

    @if ($quotation->canConvert())
        @can('sales.orders.create')
            <form method="POST" action="{{ route('sales.quotations.convert', $quotation->id) }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">{{ __('Turn into a sales order') }}</h3></div>
                <div class="card-body row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="order_date" class="form-label">{{ __('Order date') }}</label>
                        <input type="date" id="order_date" name="order_date" value="{{ app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="delivery_date" class="form-label">{{ __('Delivery date') }}</label>
                        <input type="date" id="delivery_date" name="delivery_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="warehouse_id" class="form-label">{{ __('Ship from') }}</label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle"></i> {{ __('Create sales order') }}</button></div>
                </div>
            </form>
        @endcan
    @endif
@endsection
