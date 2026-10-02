<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OccurrenceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'occurrence_id', 'actor_id', 'from_status', 'to_status', 'old_date', 'new_date', 'event_type', 'created_at',
    ];

    protected function casts(): array
    {
        return ['old_date' => 'date', 'new_date' => 'date', 'created_at' => 'datetime'];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(Occurrence::class);
    }
}
