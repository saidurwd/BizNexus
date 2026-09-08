@extends('layouts.erp')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Information</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        @if($user->profile_picture && Storage::disk('public')->exists($user->profile_picture))
                            <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        @else
                            <i class="bi bi-person-circle" style="font-size: 6rem; color: #6c757d;"></i>
                        @endif
                    </div>
                    <h4>{{ $user->name }}</h4>
                    <p class="text-muted">{{ $user->email }}</p>
                    <p class="text-muted">
                        <small>Member since {{ $user->created_at->format('Y-m-d') }}</small>
                    </p>
                </div>
            </div>

            @php
                $companies = app(\Modules\Core\Services\CompanyContextService::class)->getUserCompanies();
                $activeCompanyId = session('active_company_id');
            @endphp

            @if($companies->count() > 1)
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Change Company</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('company.switch') }}">
                            @csrf
                            <div class="form-group">
                                <label for="company_id">Select Company</label>
                                <select class="form-control" name="company_id" onchange="this.form.submit()">
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ $company->id == $activeCompanyId ? 'selected' : '' }}>
                                            {{ $company->code }} — {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Profile Information</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Update Password</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title text-danger">Delete Account</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
