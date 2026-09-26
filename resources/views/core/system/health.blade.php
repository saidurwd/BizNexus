@extends('layouts.erp')

@section('title', __('System Health'))

@php $colour = ['ok' => 'success', 'warning' => 'warning', 'error' => 'danger']; $icon = ['ok' => 'bi-check-circle-fill', 'warning' => 'bi-exclamation-triangle-fill', 'error' => 'bi-x-octagon-fill']; @endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('System Health') }}</h1>
        <a href="{{ route('core.system.health') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> {{ __('Check again') }}</a>
    </div>
@endsection

@section('content')
    <div class="alert alert-{{ $colour[$overall] }} d-flex align-items-center gap-2">
        <i class="bi {{ $icon[$overall] }} fs-4"></i>
        <div>
            @if ($overall === 'ok')
                {{ __('Everything is working.') }}
            @elseif ($overall === 'warning')
                {{ __('Working, with warnings to look at.') }}
            @else
                {{ __('Something needs attention now.') }}
            @endif
            <small class="d-block">{{ __('Checked at :time', ['time' => now()->format('Y-m-d H:i:s')]) }}</small>
        </div>
    </div>

    <div class="card">
        <ul class="list-group list-group-flush">
            @foreach ($checks as $check)
                <li class="list-group-item d-flex gap-3 align-items-start">
                    <i class="bi {{ $icon[$check['status']] }} text-{{ $colour[$check['status']] }} fs-5" aria-label="{{ $check['status'] }}"></i>
                    <div>
                        <strong>{{ __($check['name']) }}</strong>
                        <div class="text-body-secondary">{{ $check['detail'] }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
