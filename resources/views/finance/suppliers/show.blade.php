@extends('layouts.erp')

@section('title', 'Supplier Details')

@section('content_header')
    <h1>Supplier: {{ $supplier->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Code</th>
                    <td>{{ $supplier->supplier_code }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $supplier->name }}</td>
                </tr>
                <tr>
                    <th>Contact Person</th>
                    <td>{{ $supplier->contact_person ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $supplier->email ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $supplier->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Tax Number</th>
                    <td>{{ $supplier->tax_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td>{{ $supplier->address ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $supplier->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.suppliers.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
