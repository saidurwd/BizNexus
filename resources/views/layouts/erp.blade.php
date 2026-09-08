@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('classes_body', $layoutHelper->makeBodyClasses())
@section('body_data', $layoutHelper->makeBodyData())

@section('body')
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

            @hasSection('content_header')
                <div class="app-content-header">
                    <div class="container-fluid">
                        @yield('content_header')
                    </div>
                </div>
            @endif

            <div class="app-content @unless(View::hasSection('content_header')) pt-3 @endunless">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>

        @if($layoutHelper->isFixedFooterEnabled() || View::hasSection('footer'))
            @include('adminlte::partials.footer.footer')
        @endif

        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('content_top_nav_right')
    @auth
        @php
            $companies = \Modules\Core\Services\CompanyContextService::make()->getUserCompanies();
            $activeCompanyId = session('active_company_id');
        @endphp

        @if($companies->count() > 1)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-building"></i>
                    <span class="d-none d-md-inline">
                        {{ $companies->firstWhere('id', $activeCompanyId)?->code ?? 'Select Company' }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($companies as $company)
                        <li>
                            <form method="POST" action="{{ route('company.selection.submit') }}" class="dropdown-item-form">
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

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
