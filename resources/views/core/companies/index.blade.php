@extends('layouts.erp')

@section('title', 'Companies')

@section('content_header')
    <h1>Companies</h1>
    <div class="mt-2">
        <a href="{{ route('core.companies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Company
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
                        <th>Legal Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>{{ $company->code }}</td>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->legal_name ?? '-' }}</td>
                            <td>{{ $company->email ?? '-' }}</td>
                            <td>{{ $company->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $company->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $company->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('core.companies.edit', $company->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.companies.destroy', $company->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($company->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No companies found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
