@extends('layouts.erp')

@section('title', 'Suppliers')

@section('content_header')
    <h1>Suppliers</h1>
    <div class="mt-2">
        <a href="{{ route('finance.suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Supplier
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
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->supplier_code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?? '-' }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $supplier->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.suppliers.show', $supplier->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('finance.suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($supplier->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No suppliers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
