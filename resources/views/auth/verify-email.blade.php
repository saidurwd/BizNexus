@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Verify your email'))

@section('auth_header', __('Verify your email address'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('Please confirm your email address with the link we sent you. If it did not arrive, we can send another.') }}</p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success py-2">{{ __('A new verification link has been sent to your email address.') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">{{ __('Resend verification email') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link">{{ __('Sign out') }}</button>
        </form>
    </div>
@endsection
