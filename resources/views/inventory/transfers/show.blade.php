@extends('layouts.erp')

@section('title', __('Stock transfer :number', ['number' => $transfer->transfer_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Stock transfer :number', ['number' => $transfer->transfer_number]) }}</h1>
        <x-status-badge :status="$transfer->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Stock transfer')" :subtitle="$transfer->transfer_number" />

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('From warehouse') }}</dt>
                <dd class="col-sm-9">{{ $transfer->fromWarehouse?->code }} — {{ $transfer->fromWarehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('To warehouse') }}</dt>
                <dd class="col-sm-9">{{ $transfer->toWarehouse?->code }} — {{ $transfer->toWarehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('Date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($transfer->transfer_date) }}</dd>
                <dt class="col-sm-3">{{ __('Created by') }}</dt>
                <dd class="col-sm-9">{{ $transfer->createdBy?->name ?? '—' }}</dd>
                @if ($transfer->notes)
                    <dt class="col-sm-3">{{ __('Notes') }}</dt>
                    <dd class="col-sm-9">{!! nl2br(e($transfer->notes)) !!}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('Quantity') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($transfer->lines as $line)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a> {{ $line->product?->name }}</td>
                            <td class="text-end">{{ Formatter::quantity($line->quantity, $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
    <x-print-signatures :labels="[__('Issued by'), __('Received by'), __('Approved by')]" />
@endsection
