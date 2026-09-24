<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Tenant;
use Modules\Core\Models\UserCompany;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'profile_picture', 'status'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Mirrors the column default so new, unsaved-then-authenticated users are active.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->tenant_id ??= auth()->user()?->tenant_id ?? Tenant::default()->id;
        });
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Whether the user may sign in or use tokens here: active user, active tenant, and the tenant's data
     * region served by this deployment.
     */
    public function canSignIn(): bool
    {
        $tenant = $this->tenant;
        $deploymentRegion = config('tenancy.data_region');

        return $this->isActive()
            && $tenant?->isActive()
            && (! $deploymentRegion || ! $tenant->data_region || $tenant->data_region === $deploymentRegion);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userCompanies()
    {
        return $this->hasMany(UserCompany::class);
    }

    public function companyUserRoles()
    {
        return $this->hasMany(CompanyUserRole::class);
    }

    public function adminlte_image()
    {
        if ($this->profile_picture) {
            return asset('storage/'.$this->profile_picture);
        }

        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
