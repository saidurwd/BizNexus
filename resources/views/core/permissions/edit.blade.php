@extends('layouts.erp')

@section('title', __('Edit Permission'))

@section('content_header')
    <h1>{{ __('Edit Permission') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.permissions.update', $permission->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $permission->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="slug">{{ __('Slug') }}</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $permission->slug) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="group">{{ __('Group') }}</label>
                            <input type="text" class="form-control" name="group" value="{{ old('group', $permission->group) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description">{{ __('Description') }}</label>
                            <input type="text" class="form-control" name="description" value="{{ old('description', $permission->description) }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Update Permission') }}</button>
                    <a href="{{ route('core.permissions.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
