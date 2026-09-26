<form method="POST" action="{{ $action }}" class="row g-2 align-items-end">
    @csrf
    @method($method)
    <div class="col-md-2">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" value="{{ $warehouse?->code }}" class="form-control" maxlength="20" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ $warehouse?->name }}" class="form-control" maxlength="255" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Branch') }}</label>
        <select name="branch_id" class="form-select">
            <option value="">—</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $warehouse?->branch_id === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select">
            <option value="active" @selected(($warehouse?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($warehouse?->status === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="col-md-9">
        <label class="form-label">{{ __('Address') }}</label>
        <input type="text" name="address" value="{{ $warehouse?->address }}" class="form-control" maxlength="1000">
    </div>
    <div class="col-md-3">
        <div class="form-check mb-2">
            <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is-default-{{ $warehouse?->id ?? 'new' }}" @checked($warehouse?->is_default)>
            <label class="form-check-label" for="is-default-{{ $warehouse?->id ?? 'new' }}">{{ __('Default warehouse') }}</label>
        </div>
    </div>
    <div class="col-12"><button type="submit" class="btn btn-primary">{{ $submitLabel }}</button></div>
</form>
