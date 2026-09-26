@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Two-factor authentication'))

@section('auth_header', __('Two-factor authentication'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        @include('auth._field', ['name' => 'code', 'label' => __('Authentication code'), 'attributes' => 'inputmode=numeric autofocus autocomplete=one-time-code'])
        @include('auth._field', ['name' => 'recovery_code', 'label' => __('Or a recovery code'), 'attributes' => 'autocomplete=off'])
        <button type="submit" class="btn btn-primary w-100">{{ __('Verify') }}</button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
@endsection
