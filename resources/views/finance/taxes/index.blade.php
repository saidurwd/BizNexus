@extends('adminlte::page')

@section('title', 'Tax Codes')

@section('content_header')
    <h1>Tax Codes</h1>
    <div class="mt-2">
        <a href="{{ route('finance.taxes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Tax Code
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
                        <th class="text-right">Rate %</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxes as $tax)
                        <tr>
                            <td>{{ $tax->tax_code }}</td>
                            <td>{{ $tax->tax_name }}</td>
                            <td class="text-right">{{ number_format($tax->tax_rate, 2) }}%</td>
                            <td>{{ $tax->tax_type }}</td>
                            <td>
                                <span class="badge bg-{{ $tax->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $tax->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.taxes.show', $tax->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('finance.taxes.edit', $tax->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.taxes.destroy', $tax->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($tax->tax_name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No tax codes found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
