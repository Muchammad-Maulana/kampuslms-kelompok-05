<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    // Field nilai yang boleh diisi saat grade dibuat atau diperbarui.
    protected $fillable = [
        'submission_id',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    // Setiap grade dimiliki oleh satu submission.
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
