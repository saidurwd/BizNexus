{{--
    Outcome of the last action (->with('success'|'error'|'warning'|'info', ...)) and any validation errors,
    shown on every ERP page so a refused form always says why.
--}}
@foreach (['success' => ['success', 'bi-check-circle'], 'error' => ['danger', 'bi-x-octagon'], 'warning' => ['warning', 'bi-exclamation-triangle'], 'info' => ['info', 'bi-info-circle']] as $key => [$colour, $icon])
    @if (session($key))
        <div class="alert alert-{{ $colour }} alert-dismissible d-flex gap-2 align-items-start" role="{{ $key === 'error' ? 'alert' : 'status' }}">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
        <div>
            <strong>{{ __('Please correct the following and try again:') }}</strong>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
@endif
