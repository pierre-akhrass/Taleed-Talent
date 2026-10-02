<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id', 'owner_user_id', 'month', 'timezone', 'title', 'focus_theme', 'status',
        'working_revision', 'lock_version', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'working_revision' => 'integer',
            'lock_version' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(PlanCommitment::class);
    }

    public function closures(): HasMany
    {
        return $this->hasMany(PlanClosure::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }
}
