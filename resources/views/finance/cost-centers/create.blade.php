@extends('layouts.erp')

@section('title', 'Add Cost Center')

@section('content_header')
    <h1>Add Cost Center</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.cost-centers.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_id">Parent Cost Center</label>
                            <select name="parent_id" class="form-control">
                                <option value="">— None —</option>
                                @foreach($parentOptions as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('parent_id') == $opt->id)>
                                        {{ $opt->code }} — {{ $opt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                                <option value="inactive" @selected(old('status') == 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Cost Center</button>
                    <a href="{{ route('finance.cost-centers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
