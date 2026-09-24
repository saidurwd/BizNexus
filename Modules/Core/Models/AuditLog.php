<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->created_at ??= now();
            $log->previous_hash = static::query()
                ->where('company_id', $log->company_id)
                ->whereNotNull('hash')
                ->latest('id')
                ->lockForUpdate()
                ->value('hash');
            $log->hash = $log->computeHash();
        });

        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /**
     * SHA-256 over the entry's content and the previous entry's hash, chaining each company's log so that
     * altering or removing an entry breaks every later hash.
     */
    public function computeHash(): string
    {
        return hash('sha256', json_encode([
            $this->previous_hash,
            $this->company_id,
            $this->user_id,
            $this->module,
            $this->entity_type,
            $this->entity_id,
            $this->action,
            self::canonical($this->old_values),
            self::canonical($this->new_values),
            $this->ip_address,
            $this->user_agent,
            $this->created_at?->format('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * Sort keys recursively so JSON columns that reorder keys (MySQL) still hash identically.
     */
    protected static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(self::canonical(...), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
