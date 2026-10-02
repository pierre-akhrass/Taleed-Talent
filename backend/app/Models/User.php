<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'display_name', 'email', 'normalized_email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, MustVerifyEmailTrait, TwoFactorAuthenticatable;

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_memberships')
            ->withPivot(['role', 'status', 'joined_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function isApplicationAdmin(): bool
    {
        return $this->platformRoleAssignments()
            ->where('role', 'admin')
            ->whereNull('revoked_at')
            ->exists();
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function platformRoleAssignments()
    {
        return $this->hasMany(PlatformRoleAssignment::class);
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
            'authentication_version' => 'integer',
        ];
    }
}
