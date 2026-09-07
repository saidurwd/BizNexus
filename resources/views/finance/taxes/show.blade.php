@extends('adminlte::page')

@section('title', 'Tax Code')

@section('content_header')
    <h1>Tax Code: {{ $tax->tax_name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Code</th>
                    <td>{{ $tax->tax_code }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $tax->tax_name }}</td>
                </tr>
                <tr>
                    <th>Rate</th>
                    <td>{{ number_format($tax->tax_rate, 2) }}%</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ $tax->tax_type }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $tax->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $tax->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $tax->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
