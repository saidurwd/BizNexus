@extends('adminlte::page')

@section('title', 'Cost Centers')

@section('content_header')
    <h1>Cost Centers</h1>
    <div class="mt-2">
        <a href="{{ route('finance.cost-centers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Cost Center
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
                        <th>Parent</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costCenters as $cc)
                        <tr>
                            <td>{{ $cc->code }}</td>
                            <td>{{ $cc->name }}</td>
                            <td>{{ $cc->parent?->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $cc->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $cc->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.cost-centers.edit', $cc->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.cost-centers.destroy', $cc->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($cc->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No cost centers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
