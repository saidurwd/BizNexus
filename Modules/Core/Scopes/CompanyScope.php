<?php

namespace Modules\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\Services\CompanyContextService;

/**
 * Restricts queries to the active company. Without an active company it matches nothing (fail closed);
 * code that must work across companies has to opt out explicitly with withoutGlobalScope(CompanyScope::class).
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CompanyContextService::class)->getActiveCompanyId();

        if ($companyId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $companyId);
    }
}
