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
                    @yield('content')
                </div>
            </div>
        </main>

        @include('adminlte::partials.footer.footer')

        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('content_top_nav_right')
    @auth
        @php
            $companies = app(\Modules\Core\Services\CompanyContextService::class)->getUserCompanies();
            $activeCompanyId = session('active_company_id');
        @endphp

        @if($companies->count() > 1)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-building"></i>
                    <span class="d-none d-md-inline ms-1">
                        {{ $companies->firstWhere('id', $activeCompanyId)?->code ?? 'Select Company' }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    @foreach($companies as $company)
                        <li>
                            <form method="POST" action="{{ route('company.switch') }}" class="m-0 p-0">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company->id }}">
                                <button type="submit" class="dropdown-item {{ $company->id == $activeCompanyId ? 'active' : '' }}">
                                    {{ $company->code }} — {{ $company->name }}
                                    @if($company->id == $activeCompanyId)
                                        <i class="bi bi-check2 ms-auto"></i>
                                    @endif
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
    @endauth
@show

@section('footer')
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <strong>
                    <a href="{{ config('app.url') }}" target="_blank" rel="noopener noreferrer">
                        {{ config('app.name', 'BizNexus') }}
                    </a>
                </strong>
            </div>
            <div class="col-sm-6 text-sm-end">
                <span class="text-body-secondary">
                    &copy; {{ now()->format('Y') }} All rights reserved.
                </span>
            </div>
        </div>
    </div>
@stop

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <style>
        .app-footer {
            background: #f8f9fa;
            padding: 0.75rem 0;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .app-footer a {
            color: #6c757d;
            text-decoration: none;
        }
        .app-footer a:hover {
            text-decoration: underline;
        }
    </style>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
