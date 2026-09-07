<!-- Breadcrumbs Component -->
@props([
    'homeLabel' => 'Home',
    'homeUrl' => '/dashboard',
])

@php
    $crumbs = $breadcrumbs ?? collect();
@endphp

@if($crumbs->count() > 0)
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0 text-dark">
                    {{ $crumbs->last()['label'] ?? 'Dashboard' }}
                </h1>
            </div>
            <div class="col-auto">
                <ol class="breadcrumb mb-0">
                    {{-- Home Link --}}
                    <li class="breadcrumb-item">
                        <a href="{{ url($homeUrl) }}">
                            <i class="bi bi-house"></i> {{ $homeLabel }}
                        </a>
                    </li>

                    {{-- Dynamic Breadcrumbs --}}
                    @foreach($crumbs as $crumb)
                        @if($loop->last)
                            {{-- Current Page (no link) --}}
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ $crumb['label'] }}
                            </li>
                        @else
                            <li class="breadcrumb-item">
                                @if($crumb['url'])
                                    <a href="{{ url($crumb['url']) }}">
                                        {{ $crumb['label'] }}
                                    </a>
                                @else
                                    {{ $crumb['label'] }}
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>
@endif
