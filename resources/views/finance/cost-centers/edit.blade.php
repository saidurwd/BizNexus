@extends('layouts.erp')

@section('title', __('Edit Cost Center'))

@section('content_header')
    <h1>{{ __('Edit Cost Center') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.cost-centers.update', $costCenter->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="code">{{ __('Code') }}</label>
                            <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $costCenter->code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $costCenter->name) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="parent_id">{{ __('Parent Cost Center') }}</label>
                            <select id="parent_id" name="parent_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($parentOptions as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('parent_id', $costCenter->parent_id) == $opt->id)>
                                        {{ $opt->code }} — {{ $opt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status">{{ __('Status') }}</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" @selected(old('status', $costCenter->status) === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected(old('status', $costCenter->status) === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Update Cost Center') }}</button>
                    <a href="{{ route('finance.cost-centers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
