{{-- A labelled Bootstrap input with its validation message. --}}
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $value ?? '' }}"
        class="form-control @error($name) is-invalid @enderror" {{ $attributes ?? '' }}>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
