@extends('layouts.erp')

@section('title', 'Tax Code')

@section('content_header')
    <h1>Tax Code: {{ $tax->tax_code }} — {{ $tax->tax_name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th style="width: 220px">Type</th><td>{{ $tax->tax_type }}</td></tr>
                <tr><th>Rate today</th><td>{{ Formatter::percent($tax->rateOn(now())) }}%</td></tr>
                <tr><th>Jurisdiction</th><td>{{ $tax->country_code ?? '—' }}{{ $tax->region_code ? ' / '.$tax->region_code : '' }}</td></tr>
                <tr><th>Price inclusive</th><td>{{ $tax->is_inclusive ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Recoverable</th><td>{{ $tax->is_recoverable ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Input tax account</th><td>{{ $tax->inputAccount ? $tax->inputAccount->account_code.' — '.$tax->inputAccount->account_name : '—' }}</td></tr>
                <tr><th>Output tax account</th><td>{{ $tax->outputAccount ? $tax->outputAccount->account_code.' — '.$tax->outputAccount->account_name : '—' }}</td></tr>
                <tr><th>Status</th><td><span class="badge bg-{{ $tax->status === 'active' ? 'success' : 'secondary' }}">{{ $tax->status }}</span></td></tr>
            </table>
        </div>
    </div>

    @unless ($tax->is_group)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Rate history</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Effective from</th><th>Effective to</th><th class="text-end">Rate %</th></tr></thead>
                    <tbody>
                        @foreach ($tax->rates as $rate)
                            <tr>
                                <td>{{ $rate->effective_from->year === 1900 ? 'Always' : $rate->effective_from->format('Y-m-d') }}</td>
                                <td>{{ $rate->effective_to?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-end">{{ Formatter::percent($rate->rate) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @can('finance.taxes.update')
                    <form method="POST" action="{{ route('finance.taxes.rates.store', $tax->id) }}" class="form-inline mt-3">
                        @csrf
                        <label for="new_rate" class="me-2">New rate %</label>
                        <input type="number" id="new_rate" name="rate" step="0.0001" min="0" max="100" class="form-control me-2" required>
                        <label for="new_effective_from" class="me-2">effective from</label>
                        <input type="date" id="new_effective_from" name="effective_from" class="form-control me-2" required>
                        <button type="submit" class="btn btn-primary">Record rate change</button>
                        @error('effective_from')<div class="text-danger ms-2">{{ $message }}</div>@enderror
                    </form>
                @endcan
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header"><h3 class="card-title">Group components</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Order</th><th>Tax</th><th class="text-end">Rate today %</th><th>Compound</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($tax->components as $component)
                            <tr>
                                <td>{{ $component->pivot->sequence }}</td>
                                <td>{{ $component->tax_code }} — {{ $component->tax_name }}</td>
                                <td class="text-end">{{ Formatter::percent($component->rateOn(now())) }}</td>
                                <td>{{ $component->pivot->is_compound ? 'Yes (on net + earlier taxes)' : 'No' }}</td>
                                <td class="text-end">
                                    @can('finance.taxes.update')
                                        <form method="POST" action="{{ route('finance.taxes.components.destroy', [$tax->id, $component->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No components yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @can('finance.taxes.update')
                    <form method="POST" action="{{ route('finance.taxes.components.store', $tax->id) }}" class="form-inline mt-3">
                        @csrf
                        <select name="component_tax_id" class="form-control me-2" required>
                            @foreach ($availableComponents as $component)
                                <option value="{{ $component->id }}">{{ $component->tax_code }} — {{ $component->tax_name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="sequence" min="1" max="99" value="{{ $tax->components->count() + 1 }}" class="form-control me-2" style="width: 80px" required>
                        <select name="is_compound" class="form-control me-2">
                            <option value="0">Not compound</option>
                            <option value="1">Compound</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Add component</button>
                    </form>
                @endcan
            </div>
        </div>
    @endunless

    <div class="mt-3">
        <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Back</a>
        @can('finance.taxes.update')
            <a href="{{ route('finance.taxes.edit', $tax->id) }}" class="btn btn-warning">Edit</a>
        @endcan
    </div>
@endsection
