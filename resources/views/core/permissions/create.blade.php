@extends('layouts.erp')

@section('title', 'Add Permission')

@section('content_header')
    <h1>Add Permission</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.permissions.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group">Group</label>
                            <input type="text" class="form-control" name="group" value="{{ old('group') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" class="form-control" name="description" value="{{ old('description') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Save Permission</button>
                    <a href="{{ route('core.permissions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
