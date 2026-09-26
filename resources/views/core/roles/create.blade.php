@extends('layouts.erp')

@section('title', __('Add Role'))

@section('content_header')
    <h1>{{ __('Add Role') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.roles.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="slug">{{ __('Slug') }}</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label>{{ __('Permissions') }}</label>
                    @foreach($permissions->groupBy('group') as $group => $perms)
                        <h5>{{ $group ?? 'General' }}</h5>
                        @foreach($perms as $permission)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="permission_{{ $permission->id }}" {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                    {{ $permission->name }} <small class="text-muted">({{ $permission->slug }})</small>
                                </label>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Role') }}</button>
                    <a href="{{ route('core.roles.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
