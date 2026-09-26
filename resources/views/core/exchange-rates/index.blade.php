@extends('layouts.erp')

@section('title', __('Exchange Rates'))

@section('content_header')
    <h1>{{ __('Exchange Rates') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.exchange-rates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('Add Exchange Rate') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Currency') }}</th>
                        <th>{{ __('Rate Date') }}</th>
                        <th>{{ __('Exchange Rate') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                        <tr>
                            <td>{{ $rate->currency->code }} - {{ $rate->currency->name }}</td>
                            <td>{{ $rate->rate_date->format('Y-m-d') }} <span class="badge text-bg-light">{{ $rate->rate_type->value }}</span></td>
                            <td>{{ Formatter::rate($rate->exchange_rate) }}</td>
                            <td>{{ $rate->source ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $rate->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $rate->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('core.exchange-rates.edit', $rate->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.exchange-rates.destroy', $rate->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(@js(__('Delete this exchange rate?')))">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('No exchange rates found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
