@extends('layouts.erp')

@section('title', 'Edit Company')

@section('content_header')
    <h1>Edit Company</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.companies.update', $company->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="code">Code</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code', $company->code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $company->name) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="legal_name">Legal Name</label>
                            <input type="text" class="form-control" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $company->email) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $company->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address">Address</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address', $company->address) }}</textarea>
                        </div>
                    </div>
                </div>


                @include('core.companies._letterhead-fields', ['company' => $company])

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tax_number">Tax Number</label>
                            <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number', $company->tax_number) }}">
                        </div>
                        <x-form.country-select :value="$company->country_code" label="Country (ISO 3166)" />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="registration_number">Registration Number</label>
                            <input type="text" class="form-control" name="registration_number" value="{{ old('registration_number', $company->registration_number) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="timezone">Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                @foreach (timezone_identifiers_list() as $zone)
                                    <option value="{{ $zone }}" @selected(old('timezone', $company->timezone) === $zone)>{{ $zone }}</option>
                                @endforeach
                            </select>
                            @error('timezone')<div class="text-danger mt-1">{{ $message }}</div>
                        <div class="mb-3">
                            <label for="locale">Default language</label>
                            <select class="form-control" id="locale" name="locale">
                                @foreach (config('app.supported_locales') as $code => $language)
                                    <option value="{{ $code }}" @selected(old('locale', $company->locale) === $code)>{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fiscal_year_start">Fiscal Year Start</label>
                            <input type="date" class="form-control" name="fiscal_year_start" value="{{ old('fiscal_year_start', $company->fiscal_year_start?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', $company->status) == 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $company->status) == 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="mb-3">
                    <input type="hidden" name="require_mfa" value="0">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="require_mfa" value="1" id="require_mfa" @checked(old('require_mfa', $company->require_mfa))>
                        <label class="form-check-label" for="require_mfa">Require two-factor authentication for everyone working in this company</label>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">Update Company</button>
                    <a href="{{ route('core.companies.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
