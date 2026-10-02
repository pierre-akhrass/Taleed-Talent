<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharingPeriod extends Model
{
    use HasUlids;

    protected $fillable = ['organization_id', 'month', 'current_summary_id', 'next_version', 'lock_version'];

    protected function casts(): array
    {
        return ['month' => 'date', 'next_version' => 'integer', 'lock_version' => 'integer'];
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(SharedSummary::class);
    }

    public function currentSummary(): BelongsTo
    {
        return $this->belongsTo(SharedSummary::class, 'current_summary_id');
    }
}
