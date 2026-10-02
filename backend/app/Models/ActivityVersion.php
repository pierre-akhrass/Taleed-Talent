<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityVersion extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'activity_id', 'version_number', 'theme', 'scope', 'original_text', 'adapted_title', 'description',
        'steps_json', 'source_document_version_id', 'source_page', 'adaptation_note', 'content_hash',
        'created_by', 'approved_by', 'approved_at', 'created_at',
    ];

    protected function casts(): array
    {
        return ['steps_json' => 'array', 'approved_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(SourceDocumentVersion::class, 'source_document_version_id');
    }
}
