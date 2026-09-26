@extends('layouts.erp')

@section('title', 'Intercompany')

@section('content_header')
    <h1>Intercompany Transactions</h1>
    <p class="text-muted mb-0">Charges to another company of the group, booked in both ledgers with each company's own rates. Balances are eliminated in consolidation.</p>
@endsection

@section('content')
    @can('finance.intercompany.create')
        <div class="card">
            <div class="card-header"><h3 class="card-title">New charge from this company</h3></div>
            <div class="card-body">
                @if ($counterparties->isEmpty())
                    <p class="mb-0 text-muted">You need the intercompany permission in at least one other company of the group.</p>
                @else
                    <form method="POST" action="{{ route('finance.intercompany.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="target_company_id">Charge to</label>
                                    <select class="form-control" id="target_company_id" name="target_company_id" required>
                                        @foreach ($counterparties as $counterparty)
                                            <option value="{{ $counterparty->id }}" @selected(old('target_company_id') == $counterparty->id)>{{ $counterparty->code }} — {{ $counterparty->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="transaction_date">Date</label>
                                    <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="currency_id">Currency</label>
                                    <select class="form-control" id="currency_id" name="currency_id" required>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>{{ $currency->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.001" min="0" value="{{ old('amount') }}" required>
                                    @error('amount')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="source_account_id">Income account here</label>
                                    <select class="form-control" id="source_account_id" name="source_account_id" required>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('source_account_id') == $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="target_account_code">Cost account code in the other company</label>
                                    <input type="text" class="form-control" id="target_account_code" name="target_account_code" value="{{ old('target_account_code') }}" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" maxlength="255" value="{{ old('description') }}" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Post in both companies</button>
                    </form>
                @endif
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>From</th><th>To</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $transaction->sourceCompany->code }}</td>
                            <td>{{ $transaction->targetCompany->code }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td class="text-right">{{ $transaction->amount }} {{ $transaction->currency->code }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No intercompany transactions.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $transactions->links() }}
        </div>
    </div>
@endsection
