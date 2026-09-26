<div class="col-sm-6 col-md-3">
    <label for="user_id" class="form-label">{{ __('User') }}</label>
    <select id="user_id" name="user_id" class="form-select" data-searchable>
        <option value="">{{ __('Everyone') }}</option>
        @foreach ($users as $option)
            <option value="{{ $option->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $option->id)>{{ $option->name }} ({{ $option->email }})</option>
        @endforeach
    </select>
</div>
