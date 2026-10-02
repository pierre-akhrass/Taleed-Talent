<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanClosure extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'owner_user_id', 'plan_id', 'revision_number', 'closed_at',
        'public_integrity_hash', 'safe_facts_json', 'pinned_version_ids_json', 'metrics_definition_version',
    ];

    protected $hidden = ['public_integrity_hash', 'safe_facts_json', 'pinned_version_ids_json'];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'closed_at' => 'datetime',
            'safe_facts_json' => 'array',
            'pinned_version_ids_json' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function privatePayload(): HasOne
    {
        return $this->hasOne(PlanClosurePrivatePayload::class, 'closure_id');
    }
}
