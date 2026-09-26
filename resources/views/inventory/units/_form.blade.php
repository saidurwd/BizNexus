<form method="POST" action="{{ $action }}" class="row g-2 align-items-end">
    @csrf
    @method($method)
    <div class="col-md-2">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" value="{{ $unit?->code }}" class="form-control" maxlength="10" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ $unit?->name }}" class="form-control" maxlength="100" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('Decimals') }}</label>
        <input type="number" name="decimals" value="{{ $unit?->decimals ?? 0 }}" class="form-control" min="0" max="4" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select">
            <option value="active" @selected(($unit?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($unit?->status === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">{{ $submitLabel }}</button></div>
</form>
