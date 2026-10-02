<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceDocumentVersion extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'source_document_id', 'version_number', 'storage_key', 'sha256', 'mime', 'byte_size',
        'rights_status', 'dependency_status', 'approval_status', 'approved_by', 'approved_at', 'created_at',
    ];

    protected function casts(): array
    {
        return ['byte_size' => 'integer', 'approved_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class, 'source_document_id');
    }
}
