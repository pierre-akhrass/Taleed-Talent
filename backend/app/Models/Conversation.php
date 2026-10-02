<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id', 'owner_user_id', 'month', 'status', 'guide_version_id', 'encrypted_payload', 'key_version', 'lock_version',
    ];

    protected $hidden = ['encrypted_payload', 'key_version'];

    protected function casts(): array
    {
        return ['month' => 'date', 'lock_version' => 'integer'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
