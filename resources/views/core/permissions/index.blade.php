@extends('layouts.erp')

@section('title', 'Permissions')

@section('content_header')
    <h1>Permissions</h1>
    <div class="mt-2">
        <a href="{{ route('core.permissions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Permission
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Group</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $group => $perms)
                        @foreach($perms as $permission)
                            <tr>
                                <td>{{ $permission->name }}</td>
                                <td>{{ $permission->slug }}</td>
                                <td>{{ $permission->group ?? '-' }}</td>
                                <td>{{ $permission->description ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('core.permissions.edit', $permission->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('core.permissions.destroy', $permission->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($permission->name) }}?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No permissions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
