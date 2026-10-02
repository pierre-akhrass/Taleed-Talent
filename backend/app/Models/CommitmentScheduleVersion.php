<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommitmentScheduleVersion extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'commitment_id', 'version_number', 'cadence', 'start_date', 'end_date',
        'weekdays_json', 'effective_from', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'weekdays_json' => 'array',
            'effective_from' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(PlanCommitment::class, 'commitment_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class, 'schedule_version_id');
    }
}
