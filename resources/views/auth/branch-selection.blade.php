@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', __('Select branch'))

@section('auth_header', __('Select your branch'))

@section('auth_body')
    <p class="text-body-secondary">{{ __('Choose the branch of :company you want to work in.', ['company' => $company->code.' — '.$company->name]) }}</p>

    <form method="POST" action="{{ route('branch.selection.submit') }}">
        @csrf
        <input type="hidden" name="company_id" value="{{ $company->id }}">
        <div class="mb-3">
            <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">{{ __('Select a branch') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                @endforeach
            </select>
            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Continue') }}</button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('company.selection') }}">{{ __('Back to company selection') }}</a>
@endsection
