@extends('layouts.erp')

@php $isCount = $adjustment->reason === 'count'; @endphp

@section('title', __('Stock adjustment :number', ['number' => $adjustment->adjustment_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Stock adjustment :number', ['number' => $adjustment->adjustment_number]) }}</h1>
        <x-status-badge :status="$adjustment->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Reason') }}</dt>
                <dd class="col-sm-9">{{ $adjustment->reasonLabel() }}</dd>
                <dt class="col-sm-3">{{ __('Warehouse') }}</dt>
                <dd class="col-sm-9">{{ $adjustment->warehouse?->code }} — {{ $adjustment->warehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('Date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($adjustment->adjustment_date) }}</dd>
                <dt class="col-sm-3">{{ __('Created by') }}</dt>
                <dd class="col-sm-9">{{ $adjustment->createdBy?->name ?? '—' }}</dd>
                @if ($adjustment->postedBy)
                    <dt class="col-sm-3">{{ __('Posted by') }}</dt>
                    <dd class="col-sm-9">{{ $adjustment->postedBy->name }} · {{ Formatter::date($adjustment->posted_at) }}</dd>
                @endif
                @if ($adjustment->journal)
                    <dt class="col-sm-3">{{ __('Journal') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('finance.journals.show', $adjustment->journal->id) }}">{{ $adjustment->journal->journal_number }}</a></dd>
                @endif
                @if ($adjustment->notes)
                    <dt class="col-sm-3">{{ __('Notes') }}</dt>
                    <dd class="col-sm-9">{!! nl2br(e($adjustment->notes)) !!}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Product') }}</th>
                        @if ($isCount)
                            <th class="text-end">{{ __('On the books') }}</th>
                            <th class="text-end">{{ __('Counted') }}</th>
                        @endif
                        <th class="text-end">{{ __('Quantity change') }}</th>
                        <th class="text-end">{{ __('Unit cost') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($adjustment->lines as $line)
                        @php $decimals = $line->product?->unit?->decimals ?? 0; @endphp
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a> {{ $line->product?->name }}</td>
                            @if ($isCount)
                                <td class="text-end">{{ Formatter::quantity($line->system_quantity, $decimals) }}</td>
                                <td class="text-end">{{ Formatter::quantity(bcadd((string) $line->system_quantity, (string) $line->quantity, 4), $decimals) }}</td>
                            @endif
                            <td class="text-end {{ bccomp((string) $line->quantity, '0', 4) < 0 ? 'text-danger' : 'text-success' }}">{{ Formatter::quantity($line->quantity, $decimals) }} {{ $line->product?->unit?->code }}</td>
                            <td class="text-end">{{ $line->unit_cost !== null ? Formatter::unitPrice($line->unit_cost) : __('Average') }}</td>
                            <td class="text-end">{{ $adjustment->isDraft() ? '—' : Formatter::amount($line->value) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        @if ($adjustment->isDraft())
            @can('inventory.adjustments.create')
                <a href="{{ route('inventory.adjustments.edit', $adjustment->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
                <form method="POST" action="{{ route('inventory.adjustments.destroy', $adjustment->id) }}" onsubmit="return confirm(@js(__('Delete this adjustment?')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endcan
            @can('inventory.adjustments.post')
                <form method="POST" action="{{ route('inventory.adjustments.post', $adjustment->id) }}" onsubmit="return confirm(@js(__('Post this adjustment? Stock and the ledger are updated at once.')))">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="bi bi-journal-check"></i> {{ __('Post') }}</button>
                </form>
            @endcan
        @endif
    </div>
@endsection
