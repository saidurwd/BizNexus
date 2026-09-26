@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Forgot password'))

@section('auth_header', __('Reset your password'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('Enter your email address and we will send you a link to choose a new password.') }}</p>
    @include('auth._status')

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        @include('auth._field', ['name' => 'email', 'label' => __('Email'), 'type' => 'email', 'value' => old('email'), 'attributes' => 'required autofocus'])
        <button type="submit" class="btn btn-primary w-100">{{ __('Email password reset link') }}</button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
@endsection
