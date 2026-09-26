@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Confirm password'))

@section('auth_header', __('Confirm your password'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        @include('auth._field', ['name' => 'password', 'label' => __('Password'), 'type' => 'password', 'attributes' => 'required autofocus autocomplete=current-password'])
        <button type="submit" class="btn btn-primary w-100">{{ __('Confirm') }}</button>
    </form>
@endsection
