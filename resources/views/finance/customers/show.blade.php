@extends('layouts.erp')

@section('title', 'Customer Details')

@section('content_header')
    <h1>Customer: {{ $customer->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Code</th>
                    <td>{{ $customer->customer_code }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $customer->name }}</td>
                </tr>
                <tr>
                    <th>Contact Person</th>
                    <td>{{ $customer->contact_person ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $customer->email ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $customer->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Tax Number</th>
                    <td>{{ $customer->tax_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td>{{ $customer->address ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $customer->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.customers.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
