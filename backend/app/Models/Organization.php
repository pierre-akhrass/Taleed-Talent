<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'sector', 'city', 'timezone', 'status'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_memberships')
            ->withPivot(['role', 'status', 'joined_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}