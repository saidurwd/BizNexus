@extends('layouts.erp')

@section('title', 'Edit Role')

@section('content_header')
    <h1>Edit Role</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $role->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $role->slug) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Permissions</label>
                    @foreach($permissions->groupBy('group') as $group => $perms)
                        <h5>{{ $group ?? 'General' }}</h5>
                        @foreach($perms as $permission)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="permission_{{ $permission->id }}" {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                    {{ $permission->name }} <small class="text-muted">({{ $permission->slug }})</small>
                                </label>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Update Role</button>
                    <a href="{{ route('core.roles.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
