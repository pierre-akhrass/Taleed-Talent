<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OccurrencePrivatePayload extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'occurrence_id';

    protected $keyType = 'string';

    protected $fillable = ['occurrence_id', 'organization_id', 'owner_user_id', 'encrypted_payload', 'key_version', 'lock_version'];

    protected $hidden = ['encrypted_payload', 'key_version'];

    protected function casts(): array
    {
        return ['lock_version' => 'integer'];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(Occurrence::class);
    }
}
