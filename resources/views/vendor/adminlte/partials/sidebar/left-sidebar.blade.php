@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('sidebarItemHelper', 'JeroenNoten\LaravelAdminLte\Helpers\SidebarItemHelper')

@php
    $sidebarMenuId = 'adminlte-sidebar-menu';

    $sidebarMenu = $adminlte->menu('sidebar');

    $sidebarSearchItems = array_filter($sidebarMenu, function ($item) use ($sidebarItemHelper) {
        return $sidebarItemHelper->isSearch($item);
    });

    $sidebarMenuItems = array_filter($sidebarMenu, function ($item) use ($sidebarItemHelper) {
        return ! $sidebarItemHelper->isSearch($item);
    });

    $activeCompany = auth()->check()
        ? app(\Modules\Core\Services\CompanyContextService::class)->getActiveCompany()
        : null;

    $activeBranch = auth()->check()
        ? app(\Modules\Core\Services\BranchContextService::class)->getActiveBranch()
        : null;
@endphp

<aside class="{{ $layoutHelper->makeSidebarWrapperClasses() }}" {!! $layoutHelper->makeSidebarData() !!}>

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    {{-- Sidebar search box (kept outside of the sidebar wrapper) --}}
    @foreach($sidebarSearchItems as $item)
        @include('adminlte::partials.sidebar.menu-item')
    @endforeach

    {{-- Sidebar menu --}}
    <div class="sidebar-wrapper">
        <nav id="navigation"
            aria-label="{{ config('adminlte.sidebar_nav_aria_label') ?: __('adminlte::adminlte.main_navigation') }}"
            class="mt-2">
            <ul class="{{ $layoutHelper->makeSidebarNavClasses() }}"
                id="{{ $sidebarMenuId }}"
                data-lte-toggle="treeview"
                {!! $layoutHelper->makeSidebarNavData() !!}>
                {{-- Configured sidebar links --}}
                @each('adminlte::partials.sidebar.menu-item', $sidebarMenuItems, 'item')
            </ul>
        </nav>
    </div>

    {{-- Sidebar footer: Company & Branch context --}}
    @auth
        @if($activeCompany)
            <div class="sidebar-footer mt-auto p-3 border-top border-secondary">
                <div class="text-center">
                    <small class="text-white-50 d-block text-uppercase mb-1">{{ $activeCompany->name }}</small>
                    @if($activeBranch)
                        <small class="text-white-50 d-block text-uppercase mb-1">{{ $activeBranch->name }}</small>
                    @endif
                </div>
            </div>
        @endif
    @endauth

</aside>
