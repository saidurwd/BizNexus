<div class="col-sm-3 col-md-2">
    <label for="from" class="form-label">{{ __('From') }}</label>
    <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
</div>
<div class="col-sm-3 col-md-2">
    <label for="to" class="form-label">{{ __('To') }}</label>
    <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
</div>
<div class="col-auto">
    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
    @if (array_filter($filters))
        <a href="{{ url()->current() }}" class="btn btn-link">{{ __('Clear') }}</a>
    @endif
</div>
