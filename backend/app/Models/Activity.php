<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasUlids;

    protected $fillable = ['stable_activity_key', 'kind', 'organization_id', 'owner_user_id', 'status'];

    public function scopeVisibleTo(Builder $query, User $user, string $organizationId): Builder
    {
        return $query
            ->where(function (Builder $visible) use ($organizationId, $user): void {
                $visible->where(function (Builder $source): void {
                    $source->where('kind', 'source')->where('status', 'published');
                })->orWhere(function (Builder $custom) use ($organizationId, $user): void {
                    $custom->where('kind', 'custom')
                        ->where('organization_id', $organizationId)
                        ->where('owner_user_id', $user->id)
                        ->where('status', 'published');
                });
            })
            ->whereHas('currentVersion', fn (Builder $version): Builder => $version
                ->whereNotNull('approved_at')
                ->orWhereHas('activity', fn (Builder $activity): Builder => $activity->where('kind', 'custom')));
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ActivityVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class, 'current_version_id');
    }
}
