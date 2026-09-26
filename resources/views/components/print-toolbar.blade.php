@props(['pdf' => null])

{{-- Print (and optional PDF) buttons at the top of a report or document. Hidden when printing. --}}
<div {{ $attributes->merge(['class' => 'd-flex justify-content-end gap-2 mb-2 d-print-none']) }}>
    {{ $slot }}
    @if ($pdf)
        <a href="{{ $pdf }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-filetype-pdf"></i> {{ __('PDF') }}</a>
    @endif
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button>
</div>
