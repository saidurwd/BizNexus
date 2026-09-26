@props(['status'])

@php
    $colour = match (strtoupper((string) $status)) {
        'DRAFT' => 'secondary',
        'SUBMITTED', 'PENDING' => 'warning',
        'APPROVED' => 'info',
        'POSTED', 'ACTIVE', 'OPEN' => 'primary',
        'PAID', 'PARTIALLY_PAID', 'RECONCILED' => 'success',
        'REJECTED', 'CANCELLED', 'INACTIVE', 'LOCKED' => 'danger',
        'REVERSED', 'CLOSED' => 'dark',
        default => 'light',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge text-bg-{$colour}"]) }}>{{ __(ucfirst(strtolower(str_replace('_', ' ', (string) $status)))) }}</span>
