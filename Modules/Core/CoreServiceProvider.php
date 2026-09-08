<?php

namespace Modules\Core;

use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Helpers\MenuItemHelper;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\CompanyAccessService;
use Modules\Core\Services\BranchAccessService;
use Modules\Core\Services\DepartmentAccessService;
use Modules\Core\Services\PermissionService;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyContextService::class, function ($app) {
            return new CompanyContextService();
        });

        $this->app->singleton(CompanyAccessService::class, function ($app) {
            return new CompanyAccessService();
        });

        $this->app->singleton(BranchAccessService::class, function ($app) {
            return new BranchAccessService();
        });

        $this->app->singleton(DepartmentAccessService::class, function ($app) {
            return new DepartmentAccessService();
        });

        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->bootAdminlteMenu();
        });
    }

    protected function bootAdminlteMenu(): void
    {
        try {
            $menuHelper = app(MenuItemHelper::class);
            $permissionService = app(PermissionService::class);

            if (!auth()->check()) {
                return;
            }

            $menu = $menuHelper->menu();

            $this->filterMenuByPermissions($menu, $permissionService);
        } catch (\Throwable $e) {
            // AdminLTE may not be fully loaded yet
        }
    }

    protected function filterMenuByPermissions($menu, PermissionService $permissionService): void
    {
        foreach ($menu as $index => $item) {
            if (isset($item['permission']) && !$permissionService->hasPermission($item['permission'])) {
                unset($menu[$index]);
                continue;
            }

            if (isset($item['submenu'])) {
                $item['submenu'] = array_filter($item['submenu'], function ($subItem) use ($permissionService) {
                    if (isset($subItem['permission']) && !$permissionService->hasPermission($subItem['permission'])) {
                        return false;
                    }
                    return true;
                });
                $menu[$index]['submenu'] = array_values($item['submenu']);
            }
        }

        try {
            $menuHelper = app(\JeroenNoten\LaravelAdminLte\Helpers\MenuItemHelper::class);
            $menuHelper->menu($menu);
        } catch (\Throwable $e) {
            //
        }
    }
}
