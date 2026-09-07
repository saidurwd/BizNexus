@extends('adminlte::page')

@section('title', 'Cost Centers - BizNexus')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Cost Centers</h1>
        <a href="{{ route('finance.cost-centers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Cost Center</a>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">All Cost Centers</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <input type="text" name="table_search" class="form-control float-end" placeholder="Search">
                            <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
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
                                    <td><strong>{{ $cc->code }}</strong></td>
                                    <td>{{ $cc->name }}</td>
                                    <td>{{ $cc->parent?->name ?? '—' }}</td>
                                    <td>
                                        @if($cc->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.cost-centers.edit', $cc->id) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('finance.cost-centers.destroy', $cc->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this cost center?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">
                                        <i class="bi bi-layers fa-2x d-block mb-2"></i>No cost centers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
