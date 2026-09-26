@extends('adminlte::page')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@php
    $adminlte = app(\JeroenNoten\LaravelAdminLte\AdminLte::class);
    $fixedFooter = $layoutHelper->isFixedFooterEnabled();
@endphp

@section('classes_body', $layoutHelper->makeBodyClasses())
@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="skip-links">
        <a href="#main" class="skip-link">{{ __('adminlte::adminlte.skip_to_content') }}</a>
        <a href="#navigation" class="skip-link">{{ __('adminlte::adminlte.skip_to_navigation') }}</a>
    </div>

    <div class="{{ $layoutHelper->makeWrapperClasses() }}">

        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        <main class="{{ $layoutHelper->makeContentWrapperClasses() }}">

            @if(View::hasSection('content_header') || ($breadcrumbs ?? collect())->isNotEmpty())
                <div class="app-content-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                @hasSection('content_header')
                                    @yield('content_header')
                                @endif
                            </div>

                            @if(($breadcrumbs ?? collect())->isNotEmpty())
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

            <div class="app-content @unless(View::hasSection('content_header')) pt-3 @endunless">
                <div class="container-fluid">
                    @include('layouts.partials.flash-messages')
                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="app-footer" style="display: block !important; visibility: visible !important; opacity: 1 !important; background: #f8f9fa; padding: 0.75rem 0; font-size: 0.875rem; color: #6c757d;">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <strong>
                             Copyright &copy; {{ now()->format('Y') }}
                            <a href="{{ config('app.url') }}" target="_blank" rel="noopener noreferrer" style="color: #6c757d; text-decoration: none;">
                                {{ config('app.name', 'BizNexus') }}.
                            </a>{{ __('All rights reserved.') }}
                        </strong>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span>
                            version: {{ config('app.version', '1.0.0') }}
                        </span>
                    </div>
                </div>
            </div>
        </footer>

        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('content_top_nav_right')
    @auth
        @php
            $companyContext = app(\Modules\Core\Services\CompanyContextService::class);
            $branchContext = app(\Modules\Core\Services\BranchContextService::class);
            $activeCompany = $companyContext->getActiveCompany();
            $activeBranch = $branchContext->getActiveBranch();
        @endphp

        @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
        <li class="nav-item">
            <a class="nav-link position-relative" href="{{ route('core.notifications.index') }}" title="{{ __('Notifications') }}">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if($unreadNotifications > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                @endif
                <span class="visually-hidden">{{ trans_choice(':count unread notification|:count unread notifications', $unreadNotifications) }}</span>
            </a>
        </li>
        @if($activeCompany)
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile.edit') }}#switch-company" title="{{ __('Active company and branch') }}">
                    <span class="badge text-bg-primary d-inline-flex align-items-center gap-1">
                        @if($activeCompany->logoUrl())
                            <img src="{{ $activeCompany->logoUrl() }}" alt="" style="height: 14px; max-width: 48px; background: #fff; border-radius: 2px;">
                        @else
                            <i class="bi bi-building" aria-hidden="true"></i>
                        @endif
                        {{ $activeCompany->code }}<span class="d-none d-md-inline"> · {{ $activeCompany->name }}</span>
                        @if($activeBranch)
                            <span class="d-none d-lg-inline"> · {{ $activeBranch->code }}</span>
                        @endif
                    </span>
                </a>
            </li>
        @endif
    @endauth
@show

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <style>
        @media print {
            .app-header, .app-sidebar, .app-footer, .app-content-header, .skip-links, .alert,
            form, .btn, .card-footer .pagination, .d-print-none { display: none !important; }
            .app-main, .app-content, .container-fluid { margin: 0 !important; padding: 0 !important; }
            .card { border: 0 !important; box-shadow: none !important; }
            .report-letterhead { border-bottom: 2px solid #333 !important; border-radius: 0 !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }
        .sidebar .sidebar-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .sidebar .sidebar-wrapper nav {
            flex: 1 1 auto;
            overflow-y: auto;
        }
        .sidebar .sidebar-footer {
            flex-shrink: 0;
        }
    </style>
@stop

@section('adminlte_js')
    @vite('resources/js/erp.js')
    @stack('js')
    @yield('js')
@stop
