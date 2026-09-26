@props([
    'filters' => [],
    'statuses' => [],
    'searchLabel' => __('Number, reference or name'),
    'withOverdue' => false,
])

<form method="GET" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label for="filter-q" class="form-label">{{ __('Search') }}</label>
            <input type="search" id="filter-q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="{{ $searchLabel }}">
        </div>
        @if ($statuses)
            <div class="col-sm-4 col-md-2">
                <label for="filter-status" class="form-label">{{ __('Status') }}</label>
                <select id="filter-status" name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ __(ucfirst(strtolower(str_replace('_', ' ', $status)))) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-sm-4 col-md-2">
            <label for="filter-from" class="form-label">{{ __('From') }}</label>
            <input type="date" id="filter-from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
        </div>
        <div class="col-sm-4 col-md-2">
            <label for="filter-to" class="form-label">{{ __('To') }}</label>
            <input type="date" id="filter-to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
        </div>
        <div class="col-md-2 d-flex flex-wrap gap-2 align-items-center">
            @if ($withOverdue)
                <div class="form-check w-100">
                    <input type="checkbox" id="filter-overdue" name="overdue" value="1" class="form-check-input" @checked($filters['overdue'] ?? false)>
                    <label for="filter-overdue" class="form-check-label">{{ __('Overdue only') }}</label>
                </div>
            @endif
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            @if (array_filter($filters))
                <a href="{{ url()->current() }}" class="btn btn-link">{{ __('Clear') }}</a>
            @endif
        </div>
    </div>
</form>
