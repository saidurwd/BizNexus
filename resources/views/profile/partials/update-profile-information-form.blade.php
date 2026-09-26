<p class="text-muted mb-3">{{ __('Update your account\'s profile information and email address.') }}</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="profile_picture" class="form-label">{{ __('Profile Picture') }}</label>
        <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/jpeg,image/png">
        @error('profile_picture')
            <div class="text-danger mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
        @error('name')
            <div class="text-danger mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">{{ __('Email') }}</label>
        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
        @error('email')
            <div class="text-danger mt-1">{{ $message }}</div>
        @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-sm text-muted">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="btn btn-link btn-sm p-0">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-success">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>

        @endif
    </div>

    <div class="mb-3">
        <label for="locale" class="form-label">{{ __('Language') }}</label>
        <select id="locale" name="locale" class="form-control">
            <option value="">{{ __('Company default') }}</option>
            @foreach (config('app.supported_locales') as $code => $language)
                <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $language }}</option>
            @endforeach
        </select>
        @error('locale')<div class="text-danger mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

        @if (session('status') === 'profile-updated')
            <span class="text-success">{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
