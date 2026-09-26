<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Models\Company;

/**
 * A parent company and the companies it consolidates, with the parent's ownership of each.
 */
class ConsolidationGroup extends Model
{
    protected $fillable = ['tenant_id', 'parent_company_id', 'name'];

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'consolidation_group_members')->withPivot('ownership_percent')->withTimestamps();
    }
}
