<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanCommitment extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'plan_id', 'activity_version_id', 'pinned_theme', 'pinned_scope', 'display_order', 'status', 'created_at',
    ];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'created_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function activityVersion(): BelongsTo
    {
        return $this->belongsTo(ActivityVersion::class);
    }

    public function scheduleVersions(): HasMany
    {
        return $this->hasMany(CommitmentScheduleVersion::class, 'commitment_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class, 'commitment_id');
    }
}
