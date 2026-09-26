@extends('layouts.erp')

@section('title', __('Add Company'))

@section('content_header')
    <h1>{{ __('Add Company') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.companies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="code">{{ __('Code') }}</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="legal_name">{{ __('Legal Name') }}</label>
                            <input type="text" class="form-control" name="legal_name" value="{{ old('legal_name') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email">{{ __('Email') }}</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone">{{ __('Phone') }}</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address">{{ __('Address') }}</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>


                @include('core.companies._letterhead-fields', ['company' => null])

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tax_number">{{ __('Tax Number') }}</label>
                            <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number') }}">
                        </div>
                        <x-form.country-select :value="null" label="Country (ISO 3166)" />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="registration_number">{{ __('Registration Number') }}</label>
                            <input type="text" class="form-control" name="registration_number" value="{{ old('registration_number') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="timezone">{{ __('Timezone') }}</label>
                            <select class="form-control" id="timezone" name="timezone">
                                @foreach (timezone_identifiers_list() as $zone)
                                    <option value="{{ $zone }}" @selected(old('timezone', 'UTC') === $zone)>{{ $zone }}</option>
                                @endforeach
                            </select>
                            @error('timezone')<div class="text-danger mt-1">{{ $message }}</div>
                        <div class="mb-3">
                            <label for="locale">{{ __('Default language') }}</label>
                            <select class="form-control" id="locale" name="locale">
                                @foreach (config('app.supported_locales') as $code => $language)
                                    <option value="{{ $code }}" @selected(old('locale', 'en') === $code)>{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fiscal_year_start">{{ __('Fiscal Year Start') }}</label>
                            <input type="date" class="form-control" name="fiscal_year_start" value="{{ old('fiscal_year_start') }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status">{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', 'active') == 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status') == 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Company') }}</button>
                    <a href="{{ route('core.companies.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
