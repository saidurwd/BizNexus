<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Enums\AccountPurpose;

class AccountMapping extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'purpose',
        'account_id',
    ];

    protected $casts = [
        'purpose' => AccountPurpose::class,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
