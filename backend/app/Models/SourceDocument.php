<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceDocument extends Model
{
    use HasUlids;

    protected $fillable = ['stable_source_key', 'display_name', 'source_kind', 'status'];

    public function versions(): HasMany
    {
        return $this->hasMany(SourceDocumentVersion::class);
    }

    public function currentApprovedVersion(): BelongsTo
    {
        return $this->belongsTo(SourceDocumentVersion::class, 'current_approved_version_id');
    }
}
