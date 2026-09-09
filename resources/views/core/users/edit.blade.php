@extends('layouts.erp')

@section('title', 'Edit User')

@section('content_header')
    <h1>Edit User</h1>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">User Information</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('core.users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" placeholder="Enter full name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" placeholder="Enter email address" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="profile_picture" accept="image/*" id="profile_picture">
                            <label class="custom-file-label" for="profile_picture">Choose file</label>
                        </div>
                    </div>
                    <small class="form-text text-muted">Max size: 2MB. Formats: jpeg, png, jpg, gif</small>
                    @if($user->profile_picture)
                        <div class="mt-3">
                            <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile" width="100" class="img-thumbnail rounded">
                        </div>
                    @endif
                </div>

                <hr class="my-4">

                <div class="form-group">
                    <label class="font-weight-bold">Companies</label>
                    <div class="row">
                        @foreach($companies as $company)
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="companies[]" value="{{ $company->id }}" id="company_{{ $company->id }}" {{ in_array($company->id, $userCompanies) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="company_{{ $company->id }}">
                                        <strong>{{ $company->code }}</strong> — {{ $company->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Branches</label>
                    @foreach($branches as $companyId => $companyBranches)
                        @php $company = $companyBranches->first()->company @endphp
                        <div class="card card-outline card-secondary mb-2">
                            <div class="card-body py-2">
                                <h6 class="card-title mb-2 text-muted">
                                    <i class="fas fa-building mr-1"></i>
                                    {{ $company->code }} — {{ $company->name }}
                                </h6>
                                <div class="row">
                                    @foreach($companyBranches as $branch)
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}" {{ in_array($branch->id, $userBranches) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="branch_{{ $branch->id }}">
                                                    <strong>{{ $branch->code }}</strong> — {{ $branch->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Roles</label>
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="role_{{ $role->id }}">
                                        {{ $role->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update User
                    </button>
                    <a href="{{ route('core.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
