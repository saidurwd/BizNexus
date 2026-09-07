# Dynamic Breadcrumb System - Technical Implementation Guide

This guide covers implementing a dynamic breadcrumb system for Laravel + AdminLTE that automatically generates breadcrumbs based on route structure.

---

## 1. Overview

The breadcrumb system parses the current URL path and maps segments to readable titles using a configuration or route-based approach.

**URL Structure Example:**
```
/finance/suppliers/1/edit
```
**Breadcrumb Output:**
```
Home > Finance > Suppliers > Edit Supplier
```

---

## 2. Backend Implementation

### 2.1 Breadcrumb Configuration

Create `config/breadcrumbs.php`:

```php
<?php

return [
    // Module-level breadcrumbs
    'finance' => [
        'label' => 'Finance',
        'url' => '/finance/dashboard',
    ],
    'finance.dashboard' => [
        'label' => 'Dashboard',
        'parent' => 'finance',
    ],
    
    // Chart of Accounts
    'finance.accounts' => [
        'label' => 'Chart of Accounts',
        'parent' => 'finance',
    ],
    'finance.accounts.create' => [
        'label' => 'Create Account',
        'parent' => 'finance.accounts',
    ],
    'finance.accounts.show' => [
        'label' => 'Account Details',
        'parent' => 'finance.accounts',
        'dynamic' => true, // Uses route parameter
    ],
    'finance.accounts.edit' => [
        'label' => 'Edit Account',
        'parent' => 'finance.accounts',
        'dynamic' => true,
    ],
    
    // Journals
    'finance.journals' => [
        'label' => 'Journals',
        'parent' => 'finance',
    ],
    'finance.journals.index' => [
        'label' => 'Journal Register',
        'parent' => 'finance.journals',
    ],
    'finance.journals.create' => [
        'label' => 'New Journal Entry',
        'parent' => 'finance.journals',
    ],
    'finance.journals.show' => [
        'label' => 'Journal Details',
        'parent' => 'finance.journals',
        'dynamic' => true,
    ],
    
    // Suppliers
    'finance.suppliers' => [
        'label' => 'Suppliers',
        'parent' => 'finance',
    ],
    'finance.suppliers.index' => [
        'label' => 'Supplier List',
        'parent' => 'finance.suppliers',
    ],
    'finance.suppliers.create' => [
        'label' => 'Add Supplier',
        'parent' => 'finance.suppliers',
    ],
    'finance.suppliers.show' => [
        'label' => 'Supplier Details',
        'parent' => 'finance.suppliers',
        'dynamic' => true,
    ],
    'finance.suppliers.edit' => [
        'label' => 'Edit Supplier',
        'parent' => 'finance.suppliers',
        'dynamic' => true,
    ],
    
    // Customers
    'finance.customers' => [
        'label' => 'Customers',
        'parent' => 'finance',
    ],
    'finance.customers.index' => [
        'label' => 'Customer List',
        'parent' => 'finance.customers',
    ],
    'finance.customers.create' => [
        'label' => 'Add Customer',
        'parent' => 'finance.customers',
    ],
    'finance.customers.show' => [
        'label' => 'Customer Details',
        'parent' => 'finance.customers',
        'dynamic' => true,
    ],
    
    // Reports
    'finance.reports' => [
        'label' => 'Reports',
        'parent' => 'finance',
    ],
    'finance.reports.trial-balance' => [
        'label' => 'Trial Balance',
        'parent' => 'finance.reports',
    ],
    'finance.reports.balance-sheet' => [
        'label' => 'Balance Sheet',
        'parent' => 'finance.reports',
    ],
    'finance.reports.profit-loss' => [
        'label' => 'Profit & Loss',
        'parent' => 'finance.reports',
    ],
    'finance.reports.cash-flow' => [
        'label' => 'Cash Flow',
        'parent' => 'finance.reports',
    ],
    
    // Cost Centers
    'finance.cost-centers' => [
        'label' => 'Cost Centers',
        'parent' => 'finance',
    ],
    'finance.cost-centers.create' => [
        'label' => 'Add Cost Center',
        'parent' => 'finance.cost-centers',
    ],
    'finance.cost-centers.edit' => [
        'label' => 'Edit Cost Center',
        'parent' => 'finance.cost-centers',
        'dynamic' => true,
    ],
];
```

### 2.2 BreadcrumbService

Create `app/Services/BreadcrumbService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;

class BreadcrumbService
{
    protected array $config;
    protected array $customLabels = [];

    public function __construct()
    {
        $this->config = config('breadcrumbs', []);
    }

    /**
     * Set custom labels for dynamic segments (e.g., model names)
     */
    public function setCustomLabel(string $key, string $label): self
    {
        $this->customLabels[$key] = $label;
        return $this;
    }

    /**
     * Set multiple custom labels at once
     */
    public function setCustomLabels(array $labels): self
    {
        $this->customLabels = array_merge($this->customLabels, $labels);
        return $this;
    }

    /**
     * Generate breadcrumbs for current route
     */
    public function generate(): Collection
    {
        $routeName = Route::currentRouteName();
        
        if (!$routeName) {
            return collect();
        }

        return $this->buildBreadcrumbChain($routeName);
    }

    /**
     * Build the full breadcrumb chain by traversing parent hierarchy
     */
    protected function buildBreadcrumbChain(string $routeName, ?string $parameter = null): Collection
    {
        $crumbs = collect();
        
        // Handle parameterized routes (e.g., finance.suppliers.show with ID)
        $baseRoute = $this->getBaseRoute($routeName);
        
        // Get all possible route variants
        $routeVariants = [
            $routeName,
            $baseRoute,
        ];
        
        if ($baseRoute !== $routeName) {
            $routeVariants[] = $baseRoute . '.index';
        }

        foreach ($routeVariants as $variant) {
            if (isset($this->config[$variant])) {
                $crumbConfig = $this->config[$variant];
                
                // Add parent breadcrumbs first
                if (isset($crumbConfig['parent'])) {
                    $parentCrumbs = $this->buildBreadcrumbChain($crumbConfig['parent']);
                    $crumbs = $crumbs->merge($parentCrumbs);
                }
                
                // Build current crumb
                $label = $this->getLabel($variant, $crumbConfig, $parameter);
                
                $crumbs->push([
                    'label' => $label,
                    'url' => $crumbConfig['url'] ?? $this->getUrlForRoute($variant),
                    'active' => $variant === $routeName,
                ]);
                
                break;
            }
        }

        return $crumbs;
    }

    /**
     * Get label, checking custom labels and dynamic config
     */
    protected function getLabel(string $routeName, array $config, ?string $parameter): string
    {
        // Check custom labels first
        if (isset($this->customLabels[$routeName])) {
            return $this->customLabels[$routeName];
        }

        // Check for dynamic parameter
        if (isset($config['dynamic']) && $config['dynamic'] && $parameter) {
            return $config['label'];
        }

        return $config['label'];
    }

    /**
     * Get base route without action (e.g., finance.suppliers from finance.suppliers.show)
     */
    protected function getBaseRoute(string $routeName): string
    {
        $segments = explode('.', $routeName);
        
        // Common action suffixes to strip
        $actions = ['index', 'create', 'edit', 'show', 'update', 'store', 'destroy'];
        $lastSegment = end($segments);
        
        if (in_array($lastSegment, $actions)) {
            array_pop($segments);
            return implode('.', $segments);
        }
        
        return $routeName;
    }

    /**
     * Get URL for a route name
     */
    protected function getUrlForRoute(string $routeName): ?string
    {
        try {
            return route($routeName);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate breadcrumbs with model data for dynamic routes
     */
    public function generateForModel(string $routeName, ?object $model = null, ?string $modelLabelField = 'name'): Collection
    {
        if ($model) {
            $label = $model->{$modelLabelField} ?? 'Details';
            $this->setCustomLabel($routeName, $label);
        }
        
        return $this->generate();
    }
}
```

### 2.3 Breadcrumb View Composer

Create `app/View/Composers/BreadcrumbComposer.php`:

```php
<?php

namespace App\View\Composers;

use App\Services\BreadcrumbService;
use Illuminate\View\View;

class BreadcrumbComposer
{
    public function __construct(
        protected BreadcrumbService $breadcrumbs
    ) {}

    public function compose(View $view): void
    {
        $view->with('breadcrumbs', $this->breadcrumbs->generate());
    }
}
```

### 2.4 Register the Composer

In `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\View;
use App\View\Composers\BreadcrumbComposer;

public function boot(): void
{
    // Breadcrumb composer for all views
    View::composer('*', BreadcrumbComposer::class);
}
```

---

## 3. Frontend Implementation

### 3.1 Blade Component

Create `resources/views/components/breadcrumbs.blade.php`:

```php
@props([
    'crumbs' => [],
    'homeLabel' => 'Home',
    'homeUrl' => '/dashboard',
])

@if(count($crumbs) > 0)
    <!-- Breadcrumbs -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        {{ last($crumbs)['label'] ?? 'Page' }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ $homeUrl }}">
                                <i class="bi bi-house-door"></i> {{ $homeLabel }}
                            </a>
                        </li>
                        
                        @foreach($crumbs as $index => $crumb)
                            @if($loop->last)
                                <!-- Current page - no link -->
                                <li class="breadcrumb-item active">
                                    {{ $crumb['label'] }}
                                </li>
                            @else
                                <li class="breadcrumb-item {{ $crumb['active'] ? 'active' : '' }}">
                                    @if($crumb['url'])
                                        <a href="{{ $crumb['url'] }}">
                                            {{ $crumb['label'] }}
                                        </a>
                                    @else
                                        {{ $crumb['label'] }}
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endif
```

### 3.2 AdminLTE Layout Integration

Update your layout file (e.g., `resources/views/layouts/app.blade.php` or `vendor/adminlte/page.blade.php`):

```php
{{-- Add after content_header section or before main content --}}
<x-breadcrumbs />
```

Or manually add to your blade template:

```php
@php
    $breadcrumbs = $breadcrumbs ?? collect();
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col">
                <h1 class="m-0">{{ $pageTitle ?? 'Dashboard' }}</h1>
            </div>
            <div class="col-auto">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">
                            <i class="bi bi-house"></i>
                        </a>
                    </li>
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                            @if($loop->last)
                                {{ $crumb['label'] }}
                            @else
                                <a href="{{ $crumb['url'] ?? '#' }}">
                                    {{ $crumb['label'] }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>
```

### 3.3 AdminLTE Master Template Override

Create `resources/views/vendor/adminlte/partials/main-header.blade.php`:

```php
{{-- Custom header with breadcrumbs --}}
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    {{-- Standard AdminLTE navbar content --}}
    @include('adminlte::partials.navbar.navbar-left')
    
    @include('adminlte::partials.navbar.navbar-right')
</nav>
```

Or simply inject breadcrumbs into your content wrapper:

```php
@extends('adminlte::page')

@section('breadcrumbs')
    <x-breadcrumbs />
@endsection

@section('content_header')
    @yield('breadcrumbs')
    {{-- Your page title --}}
@endsection
```

---

## 4. Enhanced Service with URL Slug Mapping

### 4.1 URL Slug to Label Mapping

Update `BreadcrumbService` to handle URL slugs:

```php
protected array $slugLabels = [
    'accounts' => 'Chart of Accounts',
    'suppliers' => 'Suppliers',
    'customers' => 'Customers',
    'journals' => 'Journals',
    'payments' => 'Payments',
    'receipts' => 'Receipts',
    'invoices' => 'Invoices',
    'reports' => 'Reports',
    'budgets' => 'Budgets',
    'taxes' => 'Tax',
    'cost-centers' => 'Cost Centers',
    'create' => 'Create',
    'edit' => 'Edit',
    'show' => 'Details',
    'index' => 'List',
];

/**
 * Convert URL slug to readable label
 */
protected function slugToLabel(string $slug): string
{
    // Check predefined mappings
    if (isset($this->slugLabels[$slug])) {
        return $this->slugLabels[$slug];
    }
    
    // Auto-convert: convert-slug to "Convert Slug"
    return ucwords(str_replace('-', ' ', $slug));
}
```

---

## 5. Dynamic Model Labels (Advanced)

### 5.1 Controller Integration

In your controllers, pass model data for dynamic breadcrumbs:

```php
class SupplierController extends Controller
{
    public function show(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        
        // Set dynamic breadcrumb label
        app(BreadcrumbService::class)
            ->setCustomLabel('finance.suppliers.show', $supplier->name);
        
        return view('finance.suppliers.show', compact('supplier'));
    }
}
```

### 5.2 Trait for Automatic Breadcrumbs

Create a trait for reusable breadcrumb logic:

```php
<?php

namespace App\Traits;

use App\Services\BreadcrumbService;

trait HasBreadcrumbs
{
    protected BreadcrumbService $breadcrumbService;

    protected function initBreadcrumbs(): void
    {
        $this->breadcrumbService = app(BreadcrumbService::class);
    }

    protected function setBreadcrumbLabel(string $label): self
    {
        $routeName = request()->route()?->getName();
        if ($routeName) {
            $this->breadcrumbService->setCustomLabel($routeName, $label);
        }
        return $this;
    }

    protected function setBreadcrumbLabels(array $labels): self
    {
        $this->breadcrumbService->setCustomLabels($labels);
        return $this;
    }
}
```

Usage in controller:

```php
use App\Traits\HasBreadcrumbs;

class SupplierController extends Controller
{
    use HasBreadcrumbs;
    
    public function __construct()
    {
        $this->initBreadcrumbs();
    }
    
    public function show(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        
        $this->setBreadcrumbLabel($supplier->name);
        
        return view('finance.suppliers.show', compact('supplier'));
    }
}
```

---

## 6. Styling & Responsiveness

### 6.1 CSS Styles

Add to your CSS:

```css
/* Breadcrumb Styling */
.content-header {
    padding: 0.75rem 0;
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

/* Responsive */
@media (max-width: 576px) {
    .content-header .row {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .breadcrumb {
        font-size: 0.75rem;
        margin-top: 0.5rem;
    }
    
    .content-header h1 {
        font-size: 1.25rem;
    }
}
```

### 6.2 Bootstrap Icons

Ensure Bootstrap Icons are loaded:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
```

---

## 7. Edge Case Handling

### 7.1 Missing Routes

Handle routes without configuration:

```php
protected function buildBreadcrumbChain(string $routeName): Collection
{
    $crumbs = collect();
    
    // Check if route has explicit config
    if (isset($this->config[$routeName])) {
        // Use existing logic
        // ...
    } else {
        // Generate from URL segments
        $crumbs = $this->generateFromUrl();
    }
    
    return $crumbs;
}

protected function generateFromUrl(): Collection
{
    $segments = request()->segments();
    $crumbs = collect();
    $url = '';
    
    foreach ($segments as $segment) {
        $url .= '/' . $segment;
        
        $crumbs->push([
            'label' => $this->slugToLabel($segment),
            'url' => $url,
            'active' => $segment === last(request()->segments()),
        ]);
    }
    
    return $crumbs;
}
```

### 7.2 Deeply Nested Routes

Add hierarchical depth limit:

```php
public function generate(): Collection
{
    $crumbs = parent::generate();
    
    // Limit to prevent overflow on mobile
    return $crumbs->take(5);
}
```

### 7.3 Non-Descriptive URL Slugs

Map in configuration:

```php
protected array $slugLabels = [
    'u' => 'Users',
    's' => 'Settings',
    'a' => 'Analytics',
    'd' => 'Dashboard',
    'mgmt' => 'Management',
    'fin' => 'Finance',
    'acct' => 'Accounting',
];
```

---

## 8. Testing

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BreadcrumbService;

class BreadcrumbServiceTest extends TestCase
{
    protected BreadcrumbService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BreadcrumbService();
    }

    public function test_generates_breadcrumbs_for_index_route(): void
    {
        $crumbs = $this->service->generate();
        
        $this->assertNotEmpty($crumbs);
    }

    public function test_sets_custom_label(): void
    {
        $this->service->setCustomLabel('finance.suppliers.show', 'Acme Corp');
        
        $crumbs = $this->service->generate();
        
        $lastCrumb = $crumbs->last();
        $this->assertEquals('Acme Corp', $lastCrumb['label']);
    }
}
```

---

## 9. Quick Start Checklist

1. Create `config/breadcrumbs.php` with route mappings
2. Create `app/Services/BreadcrumbService.php`
3. Create `resources/views/components/breadcrumbs.blade.php`
4. Register composer in `AppServiceProvider`
5. Add `<x-breadcrumbs />` to layouts
6. Add CSS styles for responsive design
7. Update controllers with dynamic labels for show/edit routes

---

## 10. File Structure

```
app/
├── Services/
│   └── BreadcrumbService.php
├── View/
│   └── Composers/
│       └── BreadcrumbComposer.php
├── Traits/
│   └── HasBreadcrumbs.php
└── Http/
    └── Controllers/
        └── (Controllers use trait)

config/
└── breadcrumbs.php

resources/
└── views/
    ├── components/
    │   └── breadcrumbs.blade.php
    └── layouts/
        └── app.blade.php
```
