<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Please select the company and branch you want to work with.
    </div>

    <form method="POST" action="{{ route('company.selection.submit') }}" id="company-selection-form">
        @csrf

        <div>
            <x-input-label for="company_id" value="Company" />
            <select id="company_id" name="company_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">Select a company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->code }} — {{ $company->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="branch_id" value="Branch" />
            <select id="branch_id" name="branch_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required disabled>
                <option value="">Select a company first</option>
            </select>
            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Continue
            </x-primary-button>
        </div>
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

        fetch(`{{ route('branches.index') }}?company_id=${companyId}`)
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
</x-guest-layout>
