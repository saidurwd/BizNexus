@extends('layouts.erp')

@section('title', __('Inventory Valuation'))

@section('content_header')
    <h1>{{ __('Inventory Valuation') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Quantity and value of stock at the end of the day, at weighted average cost (IAS 2). Compare the total with the inventory accounts in the trial balance.') }}</p>
@endsection

@section('content')
    <form method="GET" class="card mb-3 d-print-none">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label for="date" class="form-label">{{ __('As of') }}</label>
                <input type="date" id="date" name="date" value="{{ $date }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">{{ __('Category') }}</label>
                <select id="category" name="category" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->id }}" @selected((string) $category === (string) $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('View report') }}</button></div>
            <div class="col-auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <x-report-letterhead :title="__('Inventory Valuation')" :subtitle="__('As of :date', ['date' => Formatter::date(\Carbon\CarbonImmutable::parse($date))])" />
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th class="text-end">{{ __('Average cost') }}</th>
                            <th class="text-end">{{ __('Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><code>{{ $row['product']->sku }}</code></td>
                                <td>{{ $row['product']->name }}</td>
                                <td>{{ $row['product']->category?->name ?? '—' }}</td>
                                <td class="text-end">{{ Formatter::quantity($row['quantity'], $row['product']->unit?->decimals ?? 0) }} {{ $row['product']->unit?->code }}</td>
                                <td class="text-end">{{ bccomp($row['quantity'], '0', 4) > 0 ? Formatter::unitPrice(bcdiv($row['value'], $row['quantity'], 6)) : '—' }}</td>
                                <td class="text-end">{{ Formatter::amount($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No stock on this date.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5">{{ __('Total') }}</td>
                            <td class="text-end">{{ Formatter::amount($totalValue) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
