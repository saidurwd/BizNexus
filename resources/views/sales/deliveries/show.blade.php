@extends('layouts.erp')

@section('title', __('Delivery :number', ['number' => $delivery->delivery_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Delivery :number', ['number' => $delivery->delivery_number]) }}</h1>
        <x-status-badge :status="$delivery->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Sales order') }}</dt>
                <dd class="col-sm-9"><a href="{{ route('sales.orders.show', $delivery->sales_order_id) }}">{{ $delivery->salesOrder?->order_number }}</a></dd>
                <dt class="col-sm-3">{{ __('Customer') }}</dt>
                <dd class="col-sm-9">{{ $delivery->customer?->name }}</dd>
                <dt class="col-sm-3">{{ __('Delivery date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($delivery->delivery_date) }}</dd>
                <dt class="col-sm-3">{{ __('Ship from') }}</dt>
                <dd class="col-sm-9">{{ $delivery->warehouse?->code }} — {{ $delivery->warehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('Carrier') }}</dt>
                <dd class="col-sm-9">{{ $delivery->carrier ?? '—' }} {{ $delivery->tracking_number }}</dd>
                @if ($delivery->journal)
                    <dt class="col-sm-3">{{ __('Journal') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('finance.journals.show', $delivery->journal->id) }}">{{ $delivery->journal->journal_number }}</a></dd>
                @endif
                <dt class="col-sm-3">{{ __('Delivered by') }}</dt>
                <dd class="col-sm-9">{{ $delivery->createdBy?->name ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Product') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Quantity') }}</th><th class="text-end">{{ __('Cost') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($delivery->lines as $line)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a></td>
                            <td>{{ $line->orderLine?->description }}</td>
                            <td class="text-end">{{ Formatter::quantity($line->quantity, $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                            <td class="text-end">{{ Formatter::amount($line->cost_value) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('sales.deliveries.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        <a href="{{ route('sales.deliveries.pdf', $delivery->id) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-filetype-pdf"></i> {{ __('Delivery note PDF') }}</a>
    </div>
@endsection
