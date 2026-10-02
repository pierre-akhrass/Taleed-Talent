<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanClosurePrivatePayload extends Model
{
    protected $primaryKey = 'closure_id';

    public $timestamps = false;

    protected $fillable = ['closure_id', 'organization_id', 'owner_user_id', 'encrypted_snapshot', 'key_version', 'erased_at', 'created_at'];

    protected $hidden = ['encrypted_snapshot', 'key_version'];

    protected function casts(): array
    {
        return ['erased_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function closure(): BelongsTo
    {
        return $this->belongsTo(PlanClosure::class, 'closure_id');
    }
}
