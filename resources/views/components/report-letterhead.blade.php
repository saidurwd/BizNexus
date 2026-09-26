@props(['title', 'subtitle' => null])

{{-- Company letterhead at the top of every report: shown on screen and when printed. --}}
@php
    $company = app(\Modules\Core\Services\CompanyContextService::class)->getActiveCompany();
    $contacts = array_filter([
        $company?->phone ? __('Tel').' '.$company->phone : null,
        $company?->mobile ? __('Mobile').' '.$company->mobile : null,
        $company?->email,
        $company?->website,
    ]);
    $registrations = array_filter([
        $company?->tax_number ? __('Tax number').' '.$company->tax_number : null,
        $company?->registration_number ? __('Registration').' '.$company->registration_number : null,
    ]);
@endphp

@if ($company)
    <div {{ $attributes->merge(['class' => 'report-letterhead card mb-3']) }}>
        <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-start">
            <div class="d-flex gap-3 align-items-start">
                @if ($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}" class="report-letterhead-logo" style="max-height: 64px; max-width: 200px;">
                @endif
                <div>
                    <div class="fs-5 fw-bold">{{ $company->displayName() }}</div>
                    @if ($company->address)
                        <div class="small text-body-secondary">{!! nl2br(e($company->address)) !!}</div>
                    @endif
                    @if ($contacts)
                        <div class="small text-body-secondary">{{ implode(' · ', $contacts) }}</div>
                    @endif
                    @if ($registrations)
                        <div class="small text-body-secondary">{{ implode(' · ', $registrations) }}</div>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="fs-5 fw-semibold">{{ $title }}</div>
                @if ($subtitle)
                    <div class="small">{{ $subtitle }}</div>
                @endif
                <div class="small text-body-secondary">{{ __('Currency: :code', ['code' => $company->baseCurrency?->code]) }} · {{ __('Generated :time', ['time' => now($company->timezone ?: config('app.timezone'))->format('Y-m-d H:i')]) }}</div>
            </div>
        </div>
    </div>
@endif
