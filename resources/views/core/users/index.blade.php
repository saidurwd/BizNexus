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
                        <th width="80">Profile</th>
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
                            <td class="text-center">
                                @if($user->profile_picture)
                                    <img src="{{ asset('storage/' . $user->profile_picture) }}" 
                                         alt="{{ $user->name }}" 
                                         class="img-circle elevation-2" 
                                         width="40" height="40"
                                         style="object-fit: cover;">
                                @else
                                    <div class="img-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white" 
                                         width="40" height="40" 
                                         style="width: 40px; height: 40px; border-radius: 50%;">
                                        <i class="bi bi-person" style="font-size: 20px;"></i>
                                    </div>
                                @endif
                            </td>
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
                            <td colspan="6" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection