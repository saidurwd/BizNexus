<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;

class BreadcrumbService
{
    protected array $config;
    protected array $customLabels = [];
    protected array $slugLabels = [];

    public function __construct()
    {
        $this->config = config('breadcrumbs', []);
        $this->slugLabels = [
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
            'cost-centers' => 'Cost Centers',
            'create' => 'Create',
            'edit' => 'Edit',
            'show' => 'Details',
            'index' => 'List',
            'dashboard' => 'Dashboard',
            'general-ledger' => 'General Ledger',
            'trial-balance' => 'Trial Balance',
            'profit-loss' => 'Profit & Loss',
            'balance-sheet' => 'Balance Sheet',
            'cash-flow' => 'Cash Flow',
        ];
    }

    public function setCustomLabel(string $routeName, string $label): self
    {
        $this->customLabels[$routeName] = $label;
        return $this;
    }

    public function setCustomLabels(array $labels): self
    {
        $this->customLabels = array_merge($this->customLabels, $labels);
        return $this;
    }

    public function generate(): Collection
    {
        $routeName = Route::currentRouteName();

        if (!$routeName) {
            return $this->generateFromUrl();
        }

        return $this->buildBreadcrumbChain($routeName);
    }

    public function generateForModel(string $routeName, ?object $model = null, ?string $modelLabelField = 'name'): Collection
    {
        if ($model) {
            $label = $model->{$modelLabelField} ?? 'Details';
            $this->setCustomLabel($routeName, $label);
        }

        return $this->generate();
    }

    protected function buildBreadcrumbChain(string $routeName): Collection
    {
        $crumbs = collect();

        // Get base route (without action suffix)
        $baseRoute = $this->getBaseRoute($routeName);

        // Route variants to check (most specific to least specific)
        $routeVariants = array_filter([
            $routeName,
            $baseRoute,
            $baseRoute . '.index',
            $baseRoute . '.show',
        ]);

        $matchedConfig = null;

        foreach ($routeVariants as $variant) {
            if (isset($this->config[$variant])) {
                $matchedConfig = $this->config[$variant];
                $matchedConfig['_matched_route'] = $variant;
                break;
            }
        }

        if (!$matchedConfig) {
            // Fallback: generate from URL
            return $this->generateFromUrl();
        }

        // Add parent breadcrumbs first
        if (!empty($matchedConfig['parent'])) {
            $parentCrumbs = $this->buildBreadcrumbChain($matchedConfig['parent']);
            $crumbs = $crumbs->merge($parentCrumbs);
        }

        // Build current crumb
        $label = $this->getLabel($matchedConfig['_matched_route'], $matchedConfig);
        $url = $this->getUrlForRoute($matchedConfig['_matched_route']);

        $crumbs->push([
            'label' => $label,
            'url' => $url,
            'active' => $matchedConfig['_matched_route'] === $routeName,
        ]);

        return $crumbs;
    }

    protected function getLabel(string $routeName, array $config): string
    {
        // Check custom labels first
        if (isset($this->customLabels[$routeName])) {
            return $this->customLabels[$routeName];
        }

        return $config['label'] ?? 'Page';
    }

    protected function getBaseRoute(string $routeName): string
    {
        $segments = explode('.', $routeName);
        $actions = ['index', 'create', 'edit', 'show', 'update', 'store', 'destroy'];
        $lastSegment = end($segments);

        if (in_array($lastSegment, $actions)) {
            array_pop($segments);
        }

        return implode('.', $segments);
    }

    protected function getUrlForRoute(string $routeName): ?string
    {
        try {
            $url = route($routeName);
            // Don't link to the current page
            if ($url === url()->current()) {
                return null;
            }
            return $url;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function generateFromUrl(): Collection
    {
        $segments = request()->segments();
        $crumbs = collect();
        $url = '';

        foreach ($segments as $index => $segment) {
            // Skip numeric IDs in URL
            if (is_numeric($segment)) {
                continue;
            }

            $url .= '/' . $segment;
            $isLast = $index === count($segments) - 1;

            $crumbs->push([
                'label' => $this->slugToLabel($segment),
                'url' => $isLast ? null : $url,
                'active' => $isLast,
            ]);
        }

        return $crumbs;
    }

    protected function slugToLabel(string $slug): string
    {
        // Check predefined mappings
        if (isset($this->slugLabels[$slug])) {
            return $this->slugLabels[$slug];
        }

        // Convert kebab-case or snake_case to Title Case
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}
