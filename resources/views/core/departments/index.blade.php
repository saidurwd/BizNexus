@extends('layouts.erp')

@section('title', __('Departments'))

@section('content_header')
    <h1>{{ __('Departments') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.departments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('Add Department') }}
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
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Parent') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->code }}</td>
                            <td>{{ $department->name }}</td>
                            <td>{{ $department->company?->name ?? '-' }}</td>
                            <td>{{ $department->branch?->name ?? '-' }}</td>
                            <td>{{ $department->parent?->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $department->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $department->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('core.departments.edit', $department->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.departments.destroy', $department->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($department->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No departments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
