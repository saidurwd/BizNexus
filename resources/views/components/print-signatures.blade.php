@props(['labels' => null])

{{-- Signature lines at the foot of a printed voucher. --}}
@php $labels ??= [__('Prepared by'), __('Checked by'), __('Approved by')]; @endphp
<div class="d-none d-print-flex justify-content-between gap-4 mt-5 pt-4">
    @foreach ($labels as $label)
        <div class="flex-fill text-center border-top border-dark pt-1 small">{{ $label }}</div>
    @endforeach
</div>
