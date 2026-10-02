<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedSummary extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'sharing_period_id', 'version_number', 'source_revision_fingerprint',
        'approved_payload_json', 'payload_hash', 'shared_at', 'status', 'withdrawn_at',
    ];

    protected $hidden = ['approved_payload_json', 'source_revision_fingerprint', 'payload_hash'];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'approved_payload_json' => 'array',
            'shared_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SharingPeriod::class, 'sharing_period_id');
    }
}
