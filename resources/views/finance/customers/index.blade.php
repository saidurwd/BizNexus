@extends('layouts.erp')

@section('title', 'Customers')

@section('content_header')
    <h1>Customers</h1>
    <div class="mt-2">
        <a href="{{ route('finance.customers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Customer
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->customer_code }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->contact_person ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $customer->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.customers.show', $customer->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No customers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
