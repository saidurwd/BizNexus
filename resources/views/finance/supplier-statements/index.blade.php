@extends('layouts.erp')

@section('title', 'Supplier Statements')

@section('content_header')
    <h1>Supplier Statements</h1>
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
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->supplier_code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td>
                                <a href="{{ route('finance.supplier-statements.show', $supplier->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-file-text"></i> View Statement
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No suppliers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
