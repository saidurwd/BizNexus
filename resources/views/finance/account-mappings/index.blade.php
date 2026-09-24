@extends('layouts.erp')

@section('title', 'Account Determination')

@section('content_header')
    <h1>Account Determination</h1>
    <p class="text-muted mb-0">The accounts this company uses for automatic postings (currency differences, year-end close, intercompany).</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('finance.account-mappings.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 40%">Purpose</th>
                            <th>Account</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purposes as $purpose)
                            <tr>
                                <td>{{ $purpose->label() }}</td>
                                <td>
                                    <select name="mappings[{{ $purpose->value }}]" class="form-control">
                                        <option value="">— Not mapped —</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('mappings.'.$purpose->value, $mappings[$purpose->value] ?? null) == $account->id)>
                                                {{ $account->account_code }} — {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('mappings.'.$purpose->value)
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('finance.accounts.update')
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            @endcan
        </div>
    </form>
@endsection
