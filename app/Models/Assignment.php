<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    // Field assignment yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'course_id',
        'title',
        'instructions',
        'created_by',
        'due_at',
        'max_score',
        'allow_late',
        'status',
    ];

    // due_at perlu menjadi objek datetime untuk mengecek deadline.
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'status' => 'string',
            'allow_late' => 'boolean',
        ];
    }

    // Setiap assignment dimiliki oleh satu course.
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Satu assignment dapat memiliki banyak submission mahasiswa.
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function grades(): HasManyThrough
    {
        return $this->hasManyThrough(Grade::class, Submission::class);
    }
}
