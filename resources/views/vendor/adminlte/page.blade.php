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

            {{-- Content Header (AdminLTE v4 layout) --}}
            @if(View::hasSection('content_header') || $breadcrumbs->isNotEmpty())
                <div class="app-content-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                @hasSection('content_header')
                                    @yield('content_header')
                                @endif
                            </div>

                            @if($breadcrumbs->isNotEmpty())
                                <div class="col-sm-6">
                                    <nav aria-label="{{ __('adminlte::adminlte.breadcrumb') }}">
                                        <ol class="breadcrumb float-sm-end">
                                            @foreach($breadcrumbs as $crumb)
                                                <li class="breadcrumb-item{{ $crumb['active'] ? ' active' : '' }}"
                                                    @if($crumb['active']) aria-current="page" @endif>
                                                    @if($crumb['url'])
                                                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                                                    @else
                                                        {{ $crumb['label'] }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    </nav>
                                </div>
                            @endif
                        </div>
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

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
