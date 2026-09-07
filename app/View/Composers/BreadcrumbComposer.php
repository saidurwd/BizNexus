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
