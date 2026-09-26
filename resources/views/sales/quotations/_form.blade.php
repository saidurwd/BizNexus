@php
    $document = $quotation;
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today();
@endphp
<div class="row g-3 mb-3">
    @include('sales._customer-fields')
    <div class="col-md-3">
        <label for="quotation_date" class="form-label">{{ __('Quotation date') }}</label>
        <input type="date" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', $quotation?->quotation_date?->toDateString() ?? $today->toDateString()) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label for="valid_until" class="form-label">{{ __('Valid until') }}</label>
        <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $quotation?->valid_until?->toDateString() ?? ($quotation ? null : $today->addDays(30)->toDateString())) }}" class="form-control @error('valid_until') is-invalid @enderror">
        @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@include('sales._lines')

<div class="mt-3">
    <label for="notes" class="form-label">{{ __('Notes for the customer') }}</label>
    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $quotation?->notes) }}</textarea>
</div>
