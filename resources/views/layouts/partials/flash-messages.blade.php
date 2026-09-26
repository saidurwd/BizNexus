{{-- Outcome of the last action (->with('success'|'error'|'warning'|'info', ...)), shown on every ERP page. --}}
@foreach (['success' => ['success', 'bi-check-circle'], 'error' => ['danger', 'bi-x-octagon'], 'warning' => ['warning', 'bi-exclamation-triangle'], 'info' => ['info', 'bi-info-circle']] as $key => [$colour, $icon])
    @if (session($key))
        <div class="alert alert-{{ $colour }} alert-dismissible d-flex gap-2 align-items-start" role="{{ $key === 'error' ? 'alert' : 'status' }}">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
        </div>
    @endif
@endforeach
