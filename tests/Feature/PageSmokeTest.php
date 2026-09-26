<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Modules\Core\Models\Company;

test('every screen without route parameters renders for a user holding every permission', function () {
    $routes = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => in_array('GET', $route->methods(), true)
            && in_array('auth', $route->gatherMiddleware(), true)
            && $route->parameterNames() === []
            && ! Str::startsWith($route->uri(), ['api/', 'user/', 'two-factor', 'confirm-password', 'verify-email', 'company-selection', 'branch-selection']));

    $permissions = $routes->flatMap(fn (Route $route) => collect($route->gatherMiddleware())
        ->filter(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'permission:'))
        ->map(fn (string $middleware) => Str::after($middleware, 'permission:')))
        ->unique()
        ->values()
        ->all();

    $company = Company::factory()->create();
    $user = companyUser($permissions, $company);

    $failures = $routes
        ->mapWithKeys(fn (Route $route) => [$route->uri() => actingInCompany($user, $company)->get('/'.$route->uri())->getStatusCode()])
        ->filter(fn (int $status) => $status >= 400 && $status !== 403);

    expect($routes)->not->toBeEmpty()
        ->and($failures->all())->toBe([]);
});
