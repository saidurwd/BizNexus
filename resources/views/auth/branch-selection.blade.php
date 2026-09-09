<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Please select the branch for <strong>{{ $company->code }} — {{ $company->name }}</strong>.
    </div>

    <form method="POST" action="{{ route('branch.selection.submit') }}">
        @csrf
        <input type="hidden" name="company_id" value="{{ $company->id }}">

        <div>
            <x-input-label for="branch_id" value="Branch" />
            <select id="branch_id" name="branch_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">Select a branch</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->code }} — {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('company.selection') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Company Selection
            </a>
            <x-primary-button>
                Continue
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
