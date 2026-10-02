<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDraft extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id', 'owner_user_id', 'month', 'selected_theme', 'draft_payload_json', 'step', 'lock_version',
    ];

    protected function casts(): array
    {
        return ['month' => 'date', 'draft_payload_json' => 'array', 'lock_version' => 'integer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
