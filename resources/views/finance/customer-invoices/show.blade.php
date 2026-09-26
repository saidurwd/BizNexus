@extends('layouts.erp')

@section('title', __('Customer Invoice'))

@section('content_header')
    <h1>Invoice: {{ $invoice->invoice_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Invoice Information') }}</h3>
            <div class="card-tools">
                @if($invoice->isDraft())
                    @unless ($invoice->sales_order_id)
                        <a href="{{ route('finance.customer-invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> {{ __('Edit') }}
                        </a>
                    @endunless
                    <form action="{{ route('finance.customer-invoices.destroy', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@js(__('Delete this invoice?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">{{ __('Invoice Number') }}</th>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <th>{{ __('Invoice Date') }}</th>
                    <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Due Date') }}</th>
                    <td>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Customer') }}</th>
                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                </tr>
                @if ($invoice->salesOrder)
                    <tr>
                        <th>{{ __('Sales order') }}</th>
                        <td>
                            @can('sales.orders.view')
                                <a href="{{ route('sales.orders.show', $invoice->sales_order_id) }}">{{ $invoice->salesOrder->order_number }}</a>
                            @else
                                {{ $invoice->salesOrder->order_number }}
                            @endcan
                        </td>
                    </tr>
                @endif
                <tr>
                    <th>{{ __('Currency') }}</th>
                    <td>{{ $invoice->currency?->code ?? __('Company currency') }}@if ($invoice->currency) · {{ __('rate :rate', ['rate' => Formatter::rate($invoice->exchange_rate)]) }}@endif</td>
                </tr>
                <tr>
                    <th>{{ __('Subtotal') }}</th>
                    <td class="text-end">{{ Formatter::amount($invoice->subtotal, $invoice->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Tax Amount') }}</th>
                    <td class="text-end">{{ Formatter::amount($invoice->tax_amount, $invoice->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Total Amount') }}</th>
                    <td class="text-end">{{ Formatter::amount($invoice->total_amount, $invoice->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Outstanding') }}</th>
                    <td class="text-end">{{ Formatter::amount($invoice->outstanding_amount, $invoice->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>
                        @php
                            $badgeClass = 'secondary';
                            if ($invoice->status === 'PAID') $badgeClass = 'success';
                            elseif ($invoice->status === 'APPROVED') $badgeClass = 'info';
                            elseif ($invoice->status === 'POSTED') $badgeClass = 'primary';
                            elseif ($invoice->status === 'SUBMITTED') $badgeClass = 'warning';
                            elseif ($invoice->status === 'REJECTED') $badgeClass = 'danger';
                            elseif ($invoice->status === 'CANCELLED') $badgeClass = 'dark';
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">{{ $invoice->status }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <td>{{ $invoice->description ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($invoice->lines->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Invoice Lines') }}</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('Account') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Unit Price') }}</th>
                            <th class="text-end">{{ __('Subtotal') }}</th>
                            <th>{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lines as $line)
                            <tr>
                                <td>{{ $line->account?->account_code ?? '-' }} - {{ $line->account?->account_name ?? '-' }}</td>
                                <td>{{ $line->description }}</td>
                                <td>{{ $line->quantity }}</td>
                                <td class="text-end">{{ Formatter::amount($line->unit_price, $invoice->currency?->code) }}</td>
                                <td class="text-end">{{ Formatter::amount($line->subtotal, $invoice->currency?->code) }}</td>
                                <td>
                                    {{ $line->tax?->tax_name ?? '-' }}
                                    @if($line->tax_amount > 0)
                                        ({{ Formatter::amount($line->tax_amount, $invoice->currency?->code) }})
                                    @endif
                                </td>
                                <td class="text-end">{{ Formatter::amount($line->total_amount, $invoice->currency?->code) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-4 mb-3">
        <a href="{{ route('finance.customer-invoices.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        <a href="{{ route('finance.customer-invoices.pdf', $invoice->id) }}" class="btn btn-outline-secondary ms-2" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> {{ __('PDF') }}</a>

        @if($invoice->isDraft())
            <form action="{{ route('finance.customer-invoices.submit', $invoice->id) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm(@js(__('Submit this invoice for approval?')))">
                    <i class="bi bi-send"></i> {{ __('Submit') }}
                </button>
            </form>
        @endif

        @if($invoice->isSubmitted())
            <form action="{{ route('finance.customer-invoices.approve', $invoice->id) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm(@js(__('Approve this invoice?')))">
                    <i class="bi bi-check-circle"></i> {{ __('Approve') }}
                </button>
            </form>
            <form action="{{ route('finance.customer-invoices.reject', $invoice->id) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm(@js(__('Reject this invoice?')))">
                    <i class="bi bi-x-circle"></i> {{ __('Reject') }}
                </button>
            </form>
        @endif

        @if($invoice->isApproved() || $invoice->isSubmitted())
            <form action="{{ route('finance.customer-invoices.post', $invoice->id) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm(@js(__('Post this invoice?')))">
                    <i class="bi bi-journal-check"></i> {{ __('Post') }}
                </button>
            </form>
        @endif

        @if(in_array($invoice->status, ['POSTED', 'PARTIALLY_PAID', 'PAID'], true))
            @can('finance.customer-credit-notes.create')
                <a href="{{ route('finance.customer-credit-notes.create', ['invoice' => $invoice->id]) }}" class="btn btn-outline-primary ms-2">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Issue credit note') }}
                </a>
            @endcan
            <a href="{{ route('finance.customer-invoices.e-invoice', $invoice->id) }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-filetype-xml"></i> {{ __('E-invoice (UBL)') }}
            </a>
        @endif

        @if(!$invoice->isPosted() && !$invoice->isPaid())
            <form action="{{ route('finance.customer-invoices.cancel', $invoice->id) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm(@js(__('Cancel this invoice?')))">
                    <i class="bi bi-x-octagon"></i> {{ __('Cancel') }}
                </button>
            </form>
        @endif
    </div>
    <x-attachments :document="$invoice" type="customer-invoices" />
@endsection
