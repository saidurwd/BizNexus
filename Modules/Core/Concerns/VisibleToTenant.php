<?php

namespace Modules\Core\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Tenant;

/**
 * Operational logs belong to a tenant. Rows with no tenant (for example a failed sign-in with an unknown email)
 * are shown to the default tenant, which runs the installation.
 */
trait VisibleToTenant
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $isInstallationTenant = (int) $user->tenant_id === (int) Tenant::where('code', 'DEFAULT')->value('id');

        return $query->where(fn (Builder $query) => $query
            ->where($query->qualifyColumn('tenant_id'), $user->tenant_id)
            ->when($isInstallationTenant, fn (Builder $query) => $query->orWhereNull($query->qualifyColumn('tenant_id'))));
    }
}
