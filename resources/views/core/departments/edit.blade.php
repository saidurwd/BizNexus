@extends('layouts.erp')

@section('title', 'Edit Department')

@section('content_header')
    <h1>Edit Department</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.departments.update', $department->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_id">Company</label>
                            <select name="company_id" class="form-control" required>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('company_id', $department->company_id) == $company->id)>
                                        {{ $company->code }} — {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $department->branch_id) == $branch->id)>
                                        {{ $branch->code }} — {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code', $department->code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $department->name) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_id">Parent Department</label>
                            <select name="parent_id" class="form-control">
                                <option value="">None (Top Level)</option>
                                @foreach($parentDepartments as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id', $department->parent_id) == $parent->id)>
                                        {{ $parent->code }} — {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="manager_id">Manager</label>
                            <select name="manager_id" class="form-control">
                                <option value="">Select Manager</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('manager_id', $department->manager_id) == $user->id)>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', $department->status) == 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $department->status) == 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Update Department</button>
                    <a href="{{ route('core.departments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
