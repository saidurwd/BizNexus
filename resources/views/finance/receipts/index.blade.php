@extends('layouts.erp')

@section('title', ($isBankReceipts ?? false) ? 'Bank Receipts' : 'Receipts')

@section('content_header')
    <h1>{{ ($isBankReceipts ?? false) ? 'Bank Receipts' : 'Receipts' }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.receipts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('New Receipt') }}
        </a>
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'POSTED', 'CANCELLED']" :search-label="__('Receipt number, reference or customer')" />
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ ($isBankReceipts ?? false) ? 'Bank Receipt Register' : 'Receipt Register' }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Bank Account') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td>{{ $receipt->customer?->name ?? '-' }}</td>
                            <td>{{ $receipt->bankAccount?->account_name ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($receipt->amount, $receipt->currency?->code) }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->status === 'POSTED' ? 'success' : ($receipt->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                                    {{ $receipt->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.receipts.show', $receipt->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No receipts found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($receipts->hasPages())
            <div class="card-footer">{{ $receipts->links() }}</div>
        @endif
    </div>
@endsection
