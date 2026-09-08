<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Please select the company you want to work with.
    </div>

    <form method="POST" action="{{ route('company.selection.submit') }}">
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

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Continue
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
