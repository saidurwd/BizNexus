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

        <footer class="app-footer" style="display: block !important; visibility: visible !important; opacity: 1 !important; background: #f8f9fa; padding: 0.75rem 0; font-size: 0.875rem; color: #6c757d;">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <strong>
                             Copyright &copy; {{ now()->format('Y') }}
                            <a href="{{ config('app.url') }}" target="_blank" rel="noopener noreferrer" style="color: #6c757d; text-decoration: none;">
                                {{ config('app.name', 'BizNexus') }}.
                            </a>All rights reserved.
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
            $companies = $companyContext->getUserCompanies();
            $branches = $activeCompany ? $branchContext->getAccessibleBranches($activeCompany->id) : collect();
        @endphp

        {{-- @if($companies->count() > 1)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-building"></i>
                    <span class="d-none d-md-inline ms-1">
                        {{ $activeCompany?->code ?? 'Select Company' }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    @foreach($companies as $company)
                        <li>
                            <form method="POST" action="{{ route('company.switch') }}" class="m-0 p-0">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company->id }}">
                                <button type="submit" class="dropdown-item {{ $company->id == $activeCompany?->id ? 'active' : '' }}">
                                    {{ $company->code }} — {{ $company->name }}
                                    @if($company->id == $activeCompany?->id)
                                        <i class="bi bi-check2 ms-auto"></i>
                                    @endif
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif

        @if($branches->count() > 1)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-shop"></i>
                    <span class="d-none d-md-inline ms-1">
                        {{ $activeBranch?->code ?? 'Select Branch' }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    @foreach($branches as $branch)
                        <li>
                            <form method="POST" action="{{ route('branch.switch') }}" class="m-0 p-0">
                                @csrf
                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                <button type="submit" class="dropdown-item {{ $branch->id == $activeBranch?->id ? 'active' : '' }}">
                                    {{ $branch->code }} — {{ $branch->name }}
                                    @if($branch->id == $activeBranch?->id)
                                        <i class="bi bi-check2 ms-auto"></i>
                                    @endif
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif --}}
    @endauth
@show

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <style>
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
    @stack('js')
    @yield('js')
@stop
