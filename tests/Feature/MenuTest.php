<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;

/**
 * @return list<array{text: string, url: string, can?: string}>
 */
function menuLinks(array $items): array
{
    return collect($items)->flatMap(fn (array $item) => [
        ...(isset($item['url']) && ! isset($item['type']) ? [$item] : []),
        ...menuLinks($item['submenu'] ?? []),
    ])->all();
}

test('every menu link opens a screen guarded by the same permission as the link', function () {
    $routes = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => in_array('GET', $route->methods(), true))
        ->keyBy(fn (Route $route) => $route->uri());

    $mismatches = collect(menuLinks(config('adminlte.menu')))
        ->reject(fn (array $link) => $link['url'] === 'profile')
        ->mapWithKeys(function (array $link) use ($routes) {
            $route = $routes->get($link['url']);
            $required = $route ? collect($route->gatherMiddleware())->first(fn ($m) => is_string($m) && str_starts_with($m, 'permission:')) : null;

            return [$link['text'] => match (true) {
                ! $route => 'no route for '.$link['url'],
                $required !== null && Str::after($required, 'permission:') !== ($link['can'] ?? null) => 'menu checks '.($link['can'] ?? 'nothing').', route needs '.Str::after($required, 'permission:'),
                default => null,
            }];
        })
        ->filter();

    expect($mismatches->all())->toBe([]);
});
