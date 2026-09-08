@extends('adminlte::page')

@section('title', 'Customer Statements')

@section('content_header')
    <h1>Customer Statements</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->customer_code }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>
                                <a href="{{ route('finance.customer-statements.show', $customer->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-file-text"></i> View Statement
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No customers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
