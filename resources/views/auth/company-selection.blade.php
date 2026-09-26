@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Select company'))

@section('auth_header', __('Select your company'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('Choose the company and branch you want to work in.') }}</p>

    <form method="POST" action="{{ route('company.selection.submit') }}" id="company-selection-form">
        @csrf
        <div class="mb-3">
            <label for="company_id" class="form-label">{{ __('Company') }}</label>
            <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                <option value="">{{ __('Select a company') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->code }} — {{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" disabled>
                <option value="">{{ __('Select a company first') }}</option>
            </select>
            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Continue') }}</button>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const companySelect = document.getElementById('company_id');
        if (companySelect && companySelect.value) {
            companySelect.dispatchEvent(new Event('change'));
        }
    });

    document.getElementById('company_id').addEventListener('change', function() {
        const companyId = this.value;
        const branchSelect = document.getElementById('branch_id');

        branchSelect.disabled = true;
        branchSelect.innerHTML = '<option value="">Loading...</option>';

        if (!companyId) {
            branchSelect.innerHTML = '<option value="">Select a company first</option>';
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
                }
            })
            .catch(() => {
                branchSelect.innerHTML = '<option value="">Select a company first</option>';
            });
    });
    </script>
@endsection
