@extends('layouts.erp')

@section('title', 'Edit Exchange Rate')

@section('content_header')
    <h1>Edit Exchange Rate</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.exchange-rates.update', $rate->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="currency_id">Currency</label>
                            <select name="currency_id" class="form-control" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id', $rate->currency_id) == $currency->id)>
                                        {{ $currency->code }} — {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="rate_date">Rate Date</label>
                            <input type="date" class="form-control" name="rate_date" value="{{ old('rate_date', $rate->rate_date->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exchange_rate">Exchange Rate</label>
                            <input type="number" step="0.0001" class="form-control" name="exchange_rate" value="{{ old('exchange_rate', $rate->exchange_rate) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="source">Source</label>
                            <input type="text" class="form-control" name="source" value="{{ old('source', $rate->source) }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', $rate->status) == 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $rate->status) == 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Update Exchange Rate</button>
                    <a href="{{ route('core.exchange-rates.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
