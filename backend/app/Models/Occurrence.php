<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Occurrence extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id', 'plan_id', 'commitment_id', 'generation_date', 'scheduled_date',
        'owner_user_id', 'schedule_version_id', 'status', 'lock_version', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'generation_date' => 'date',
            'scheduled_date' => 'date',
            'lock_version' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(PlanCommitment::class);
    }

    public function scheduleVersion(): BelongsTo
    {
        return $this->belongsTo(CommitmentScheduleVersion::class, 'schedule_version_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OccurrenceEvent::class);
    }

    public function privatePayload(): HasOne
    {
        return $this->hasOne(OccurrencePrivatePayload::class);
    }
}
