<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellbeingRevision extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'wellbeing_entry_id', 'organization_id', 'owner_user_id', 'revision_number', 'encrypted_payload', 'key_version', 'created_at',
    ];

    protected $hidden = ['encrypted_payload', 'key_version'];

    protected function casts(): array
    {
        return ['revision_number' => 'integer', 'created_at' => 'datetime'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(WellbeingEntry::class, 'wellbeing_entry_id');
    }
}
