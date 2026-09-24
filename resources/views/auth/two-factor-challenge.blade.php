<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}
    </div>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Authentication code')" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="recovery_code" :value="__('Or a recovery code')" />
            <x-text-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" autocomplete="off" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
