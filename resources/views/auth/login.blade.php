@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Sign in'))

@section('auth_header', __('Sign in to continue'))

@section('auth_body')
    @include('auth._status')

    <form method="POST" action="{{ route('login') }}">
        @csrf
        @include('auth._field', ['name' => 'email', 'label' => __('Email'), 'type' => 'email', 'value' => old('email'), 'attributes' => 'required autofocus autocomplete=username'])
        @include('auth._field', ['name' => 'password', 'label' => __('Password'), 'type' => 'password', 'attributes' => 'required autocomplete=current-password'])

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
                <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Sign in') }}</button>
        </div>
    </form>
@endsection

@section('auth_footer')
    @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
    @endif
@endsection
