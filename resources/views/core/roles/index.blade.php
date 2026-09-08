@extends('layouts.erp')

@section('title', 'Roles')

@section('content_header')
    <h1>Roles</h1>
    <div class="mt-2">
        <a href="{{ route('core.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Role
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
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->slug }}</td>
                            <td>{{ $role->description ?? '-' }}</td>
                            <td>
                                @foreach($role->permissions as $permission)
                                    <span class="badge bg-info">{{ $permission->slug }}</span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('core.roles.edit', $role->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($role->name) }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No roles found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
