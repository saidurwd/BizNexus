@extends('layouts.erp')

@section('title', __('Add Department'))

@section('content_header')
    <h1>{{ __('Add Department') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.departments.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_id">{{ __('Company') }}</label>
                            <select name="company_id" class="form-control" required>
                                <option value="">{{ __('Select Company') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                        {{ $company->code }} — {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="branch_id">{{ __('Branch') }}</label>
                            <select name="branch_id" class="form-control">
                                <option value="">{{ __('Select Branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                        {{ $branch->code }} — {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="code">{{ __('Code') }}</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="parent_id">{{ __('Parent Department') }}</label>
                            <select name="parent_id" class="form-control">
                                <option value="">{{ __('None (Top Level)') }}</option>
                                @foreach($parentDepartments as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>
                                        {{ $parent->code }} — {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="manager_id">{{ __('Manager') }}</label>
                            <select name="manager_id" class="form-control">
                                <option value="">{{ __('Select Manager') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('manager_id') == $user->id)>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status">{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', 'active') == 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status') == 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Department') }}</button>
                    <a href="{{ route('core.departments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
