@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Choose a new password'))

@section('auth_header', __('Choose a new password'))

@section('auth_body')
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        @include('auth._field', ['name' => 'email', 'label' => __('Email'), 'type' => 'email', 'value' => old('email', $request->email), 'attributes' => 'required autofocus autocomplete=username'])
        @include('auth._field', ['name' => 'password', 'label' => __('New password'), 'type' => 'password', 'attributes' => 'required autocomplete=new-password'])
        @include('auth._field', ['name' => 'password_confirmation', 'label' => __('Confirm new password'), 'type' => 'password', 'attributes' => 'required autocomplete=new-password'])
        <button type="submit" class="btn btn-primary w-100">{{ __('Reset password') }}</button>
    </form>
@endsection
