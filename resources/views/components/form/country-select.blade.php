@props(['name' => 'country_code', 'value' => null, 'label' => 'Country'])

<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <select class="form-control" id="{{ $name }}" name="{{ $name }}">
        <option value="">—</option>
        @foreach (\Modules\Core\Support\Countries::options() as $code => $countryName)
            <option value="{{ $code }}" @selected(old($name, $value) === $code)>{{ $countryName }} ({{ $code }})</option>
        @endforeach
    </select>
    @error($name)<div class="text-danger mt-1">{{ $message }}</div>@enderror
</div>
