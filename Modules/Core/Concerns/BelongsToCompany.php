<?php

namespace Modules\Core\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\CompanyContextService;

/**
 * Isolates a model by company: reads are limited to the active company, new records default to it,
 * and a record can neither be created for nor moved to another company while a company is active.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model) {
            $activeCompanyId = app(CompanyContextService::class)->getActiveCompanyId();

            if ($model->company_id === null) {
                $model->company_id = $activeCompanyId;

                return;
            }

            if ($activeCompanyId !== null && (int) $model->company_id !== $activeCompanyId) {
                throw new UnauthorizedCompanyAccessException((int) $model->company_id, auth()->id());
            }
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('company_id')) {
                throw new UnauthorizedCompanyAccessException((int) $model->company_id, auth()->id());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
