@extends('layouts.erp')

@section('title', __('Payment Terms'))

@section('content_header')
    <h1>{{ __('Payment Terms') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('When invoices fall due. Set a default term on each customer and supplier; invoices left without a due date get it from the term.') }}</p>
@endsection

@php
    $describe = fn ($term) => ($term->due_days === 0 ? __('Due on the invoice date') : __(':days days after :basis', ['days' => $term->due_days, 'basis' => $term->due_basis === 'end_of_month' ? __('the end of the invoice month') : __('the invoice date')]))
        .($term->discount_percent ? ' · '.__(':percent% discount if paid within :days days', ['percent' => Formatter::percent($term->discount_percent), 'days' => $term->discount_days]) : '');
@endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Rule') }}</th>
                        <th class="text-end">{{ __('Customers') }}</th>
                        <th class="text-end">{{ __('Suppliers') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('finance.payment-terms.manage')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentTerms as $term)
                        <tr>
                            <td><code>{{ $term->code }}</code></td>
                            <td>{{ $term->name }}</td>
                            <td>{{ $describe($term) }}</td>
                            <td class="text-end">{{ $usage['customers'][$term->id] ?? 0 }}</td>
                            <td class="text-end">{{ $usage['suppliers'][$term->id] ?? 0 }}</td>
                            <td><x-status-badge :status="$term->status" /></td>
                            @can('finance.payment-terms.manage')
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-term-{{ $term->id }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('finance.payment-terms.destroy', $term->id) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this payment term?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                        @can('finance.payment-terms.manage')
                            <tr class="collapse" id="edit-term-{{ $term->id }}">
                                <td colspan="7" class="bg-body-tertiary">
                                    @include('finance.payment-terms._form', ['term' => $term, 'action' => route('finance.payment-terms.update', $term->id), 'method' => 'PUT', 'submitLabel' => __('Save')])
                                </td>
                            </tr>
                        @endcan
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No payment terms yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('finance.payment-terms.manage')
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Add a payment term') }}</h3></div>
            <div class="card-body">
                @include('finance.payment-terms._form', ['term' => null, 'action' => route('finance.payment-terms.store'), 'method' => 'POST', 'submitLabel' => __('Add')])
            </div>
        </div>
    @endcan
@endsection
