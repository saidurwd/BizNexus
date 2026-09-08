@extends('layouts.erp')

@section('title', 'Users')

@section('content_header')
    <h1>Users</h1>
    <div class="mt-2">
        <a href="{{ route('core.users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add User
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
                        <th>Email</th>
                        <th>Companies</th>
                        <th>Roles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->userCompanies ?? [] as $uc)
                                    <span class="badge bg-info">{{ $uc->company->code }}</span>
                                @endforeach
                            </td>
                            <td>
                                @foreach($user->companyUserRoles ?? [] as $cur)
                                    <span class="badge bg-success">{{ $cur->role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('core.users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('core.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($user->name) }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
