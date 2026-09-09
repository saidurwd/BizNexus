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
                $activeBranchId = session('active_branch_id');
                $branchContext = app(\Modules\Core\Services\BranchContextService::class);
            @endphp

            @if($companies->count() > 1)
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Change Company & Branch</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('company.switch') }}" id="company-branch-switch-form">
                            @csrf
                            <div class="form-group">
                                <label for="company_id">Company</label>
                                <select class="form-control" name="company_id" id="profile_company_id" required>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ $company->id == $activeCompanyId ? 'selected' : '' }}>
                                            {{ $company->code }} — {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" id="branch-select-group" style="display: none;">
                                <label for="branch_id">Branch</label>
                                <select class="form-control" name="branch_id" id="profile_branch_id" disabled>
                                    <option value="">Select a company first</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const companySelect = document.getElementById('profile_company_id');
                    const branchSelectGroup = document.getElementById('branch-select-group');
                    const branchSelect = document.getElementById('profile_branch_id');
                    const form = document.getElementById('company-branch-switch-form');

                    function loadBranches(companyId) {
                        branchSelect.disabled = true;
                        branchSelect.innerHTML = '<option value="">Loading...</option>';
                        branchSelectGroup.style.display = 'block';

                        if (!companyId) {
                            branchSelect.innerHTML = '<option value="">Select a company first</option>';
                            branchSelectGroup.style.display = 'none';
                            return;
                        }

                        fetch(`{{ route('auth.branches.index') }}?company_id=${companyId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Failed to load branches');
                                }
                                return response.json();
                            })
                            .then(data => {
                                branchSelect.innerHTML = '<option value="">Select a branch</option>';
                                data.forEach(branch => {
                                    const option = document.createElement('option');
                                    option.value = branch.id;
                                    option.textContent = `${branch.code} — ${branch.name}`;
                                    branchSelect.appendChild(option);
                                });

                                branchSelect.disabled = data.length === 0;

                                if (data.length === 1) {
                                    branchSelect.value = data[0].id;
                                    form.submit();
                                } else if (data.length === 0) {
                                    branchSelect.innerHTML = '<option value="">No branches available</option>';
                                }
                            })
                            .catch(() => {
                                branchSelect.innerHTML = '<option value="">Select a company first</option>';
                                branchSelectGroup.style.display = 'none';
                            });
                    }

                    if (companySelect.value) {
                        loadBranches(companySelect.value);
                    }

                    companySelect.addEventListener('change', function() {
                        loadBranches(this.value);
                    });

                    branchSelect.addEventListener('change', function() {
                        if (this.value) {
                            form.submit();
                        }
                    });
                });
                </script>
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
