@php
    $twoFactorPending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;
@endphp

<p class="text-muted mb-3">{{ __('Protect your account with a code from an authenticator app in addition to your password.') }}</p>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($user->hasEnabledTwoFactorAuthentication())
    <p><span class="badge text-bg-success">{{ __('Enabled') }}</span> since {{ $user->two_factor_confirmed_at->format('Y-m-d') }}</p>

    <p class="mb-1">{{ __('Recovery codes (store them safely; each works once):') }}</p>
    <pre class="bg-light p-2">{{ implode("\n", $user->recoveryCodes()) }}</pre>

    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="me-2">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">{{ __('Regenerate recovery codes') }}</button>
        </form>
        <form method="POST" action="{{ route('two-factor.disable') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">{{ __('Disable') }}</button>
        </form>
    </div>
@elseif ($twoFactorPending)
    <p>{{ __('Scan this QR code with your authenticator app, then enter the code it shows.') }}</p>
    <div class="mb-3">{!! $user->twoFactorQrCodeSvg() !!}</div>

    <form method="POST" action="{{ route('two-factor.confirm') }}">
        @csrf
        <div class="mb-3">
            <label for="two_factor_code" class="form-label">{{ __('Code') }}</label>
            <input type="text" id="two_factor_code" name="code" class="form-control" inputmode="numeric" autocomplete="one-time-code">
            @error('code', 'confirmTwoFactorAuthentication')
                <div class="text-danger mt-1">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Confirm') }}</button>
    </form>
@else
    <p><span class="badge text-bg-secondary">{{ __('Not enabled') }}</span></p>
    <form method="POST" action="{{ route('two-factor.enable') }}">
        @csrf
        <button type="submit" class="btn btn-primary">{{ __('Enable two-factor authentication') }}</button>
    </form>
@endif
