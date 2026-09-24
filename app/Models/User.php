<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\UserCompany;

#[Fillable(['name', 'email', 'password', 'profile_picture'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'password' => 'hashed',
        ];
    }
}
