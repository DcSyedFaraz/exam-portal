<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamImportBatch extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'status',
        'summary_json',
        'error_report_path',
    ];

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
