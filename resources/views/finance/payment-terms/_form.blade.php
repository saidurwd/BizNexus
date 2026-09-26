@php $prefix = $term ? 'term-'.$term->id.'-' : 'new-term-'; @endphp
<form method="POST" action="{{ $action }}" class="row g-2 align-items-end">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="col-md-1">
        <label for="{{ $prefix }}code" class="form-label">{{ __('Code') }}</label>
        <input type="text" id="{{ $prefix }}code" name="code" value="{{ $term?->code }}" class="form-control" maxlength="20" required>
    </div>
    <div class="col-md-3">
        <label for="{{ $prefix }}name" class="form-label">{{ __('Name') }}</label>
        <input type="text" id="{{ $prefix }}name" name="name" value="{{ $term?->name }}" class="form-control" maxlength="255" required>
    </div>
    <div class="col-md-1">
        <label for="{{ $prefix }}due_days" class="form-label">{{ __('Days') }}</label>
        <input type="number" id="{{ $prefix }}due_days" name="due_days" value="{{ $term?->due_days ?? 30 }}" class="form-control" min="0" required>
    </div>
    <div class="col-md-2">
        <label for="{{ $prefix }}due_basis" class="form-label">{{ __('Counted from') }}</label>
        <select id="{{ $prefix }}due_basis" name="due_basis" class="form-select">
            <option value="invoice_date" @selected(($term?->due_basis ?? 'invoice_date') === 'invoice_date')>{{ __('Invoice date') }}</option>
            <option value="end_of_month" @selected($term?->due_basis === 'end_of_month')>{{ __('End of invoice month') }}</option>
        </select>
    </div>
    <div class="col-md-1">
        <label for="{{ $prefix }}discount_percent" class="form-label">{{ __('Discount %') }}</label>
        <input type="number" id="{{ $prefix }}discount_percent" name="discount_percent" value="{{ $term?->discount_percent }}" class="form-control" step="0.01" min="0" max="100">
    </div>
    <div class="col-md-1">
        <label for="{{ $prefix }}discount_days" class="form-label">{{ __('within days') }}</label>
        <input type="number" id="{{ $prefix }}discount_days" name="discount_days" value="{{ $term?->discount_days }}" class="form-control" min="0">
    </div>
    <div class="col-md-1">
        <label for="{{ $prefix }}status" class="form-label">{{ __('Status') }}</label>
        <select id="{{ $prefix }}status" name="status" class="form-select">
            <option value="active" @selected(($term?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($term?->status === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">{{ $submitLabel }}</button>
    </div>
</form>
