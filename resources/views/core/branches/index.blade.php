@extends('layouts.erp')

@section('title', __('Branches'))

@section('content_header')
    <h1>{{ __('Branches') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.branches.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('Add Branch') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->company?->name ?? '-' }}</td>
                            <td>{{ $branch->phone ?? '-' }}</td>
                            <td>{{ $branch->email ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $branch->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $branch->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('core.branches.edit', $branch->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.branches.destroy', $branch->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($branch->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No branches found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
