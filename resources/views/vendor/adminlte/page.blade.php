{{-- Custom AdminLTE page template with breadcrumbs --}}
@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@php
    $fixedFooter = $layoutHelper->isFixedFooterEnabled();
    $breadcrumbs = $breadcrumbs ?? collect();
@endphp

@section('classes_body', $layoutHelper->makeBodyClasses())
@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    {{-- Skip Links --}}
    <div class="skip-links">
        <a href="#main" class="skip-link">{{ __('adminlte::adminlte.skip_to_content') }}</a>
        <a href="#navigation" class="skip-link">{{ __('adminlte::adminlte.skip_to_navigation') }}</a>
    </div>

    <div class="{{ $layoutHelper->makeWrapperClasses() }}">

        {{-- Preloader --}}
        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Sidebar --}}
        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        <main class="{{ $layoutHelper->makeContentWrapperClasses() }}">

            {{-- Breadcrumbs (rendered before content header) --}}
            @if($breadcrumbs->count() > 0)
                <div class="app-content-breadcrumbs">
                    <div class="container-fluid">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">
                                    <i class="bi bi-house"></i>
                                </a>
                            </li>
                            @foreach($breadcrumbs as $crumb)
                                @if($loop->last)
                                    <li class="breadcrumb-item active">
                                        {{ $crumb['label'] }}
                                    </li>
                                @else
                                    <li class="breadcrumb-item">
                                        <a href="{{ $crumb['url'] ?? '#' }}">
                                            {{ $crumb['label'] }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            {{-- Content Header --}}
            @hasSection('content_header')
                <div class="app-content-header">
                    <div class="container-fluid">
                        @yield('content_header')
                    </div>
                </div>
            @endif

            {{-- Main Content --}}
            <div class="app-content @unless(View::hasSection('content_header')) pt-3 @endunless">
                <div class="container-fluid">
                    @stack('content')
                    @yield('content')
                </div>
            </div>
        </main>

        {{-- Footer --}}
        @if($fixedFooter || View::hasSection('footer'))
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Sidebar --}}
        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@push('css')
    <style>
        .app-content-breadcrumbs {
            background: #f8f9fa;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.875rem;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #6c757d;
        }
        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        .breadcrumb-item.active {
            color: #6c757d;
        }
        @media (max-width: 576px) {
            .app-content-breadcrumbs .breadcrumb {
                font-size: 0.75rem;
            }
        }
    </style>
@endpush

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
