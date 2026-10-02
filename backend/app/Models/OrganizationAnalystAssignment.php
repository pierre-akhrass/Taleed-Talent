<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationAnalystAssignment extends Model
{
    use HasUlids;

    protected $fillable = ['organization_id', 'user_id', 'assigned_by', 'assigned_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
