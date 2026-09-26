<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Concerns\BelongsToCompany;

/**
 * A supporting file (supplier bill, contract, receipt) kept on private storage with a document.
 */
class Attachment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'description',
    ];

    protected $casts = ['file_size' => 'integer'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'attachable_type', 'attachable_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
