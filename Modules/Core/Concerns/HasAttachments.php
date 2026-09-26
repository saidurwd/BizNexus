<?php

namespace Modules\Core\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Models\Attachment;

/**
 * Documents that can carry supporting files.
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }
}
