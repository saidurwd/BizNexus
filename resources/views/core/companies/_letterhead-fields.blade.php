{{-- Letterhead details printed on reports and documents. Expects $company (or null). --}}
<h5 class="mt-2">{{ __('Letterhead') }}</h5>
<p class="text-body-secondary small">{{ __('Shown on every report, statement, invoice and export of this company.') }}</p>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="logo" class="form-label">{{ __('Logo') }}</label>
            @if ($company?->logoUrl())
                <div class="mb-2 p-2 border rounded bg-body-tertiary d-inline-block">
                    <img src="{{ $company->logoUrl() }}" alt="{{ __('Current logo of :company', ['company' => $company->name]) }}" style="max-height: 64px; max-width: 240px;">
                </div>
            @endif
            <input type="file" id="logo" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">{{ __('PNG, JPEG or WebP, up to 2 MB. A wide logo on a transparent background prints best.') }}</div>
            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($company?->logo_path)
                <div class="form-check mt-2">
                    <input type="checkbox" id="remove_logo" name="remove_logo" value="1" class="form-check-input">
                    <label for="remove_logo" class="form-check-label">{{ __('Remove the current logo') }}</label>
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <label for="mobile" class="form-label">{{ __('Mobile') }}</label>
            <input type="text" id="mobile" name="mobile" class="form-control" value="{{ old('mobile', $company?->mobile) }}" maxlength="50">
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <label for="website" class="form-label">{{ __('Website') }}</label>
            <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $company?->website) }}" maxlength="255" placeholder="https://">
            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
