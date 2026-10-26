<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Material $material): void {
            if ($material->file_path) {
                Storage::disk('local')->delete($material->file_path);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query,
            'dosen' => $query->whereHas('course', fn (Builder $courses) => $courses->where('lecturer_id', $user->id)),
            'mahasiswa' => $query->whereHas(
                'course.students',
                fn (Builder $students) => $students->whereKey($user->id)
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }

    // Field material yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'course_id',
        'uploaded_by',
        'title',
        'description',
        'type',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'external_url',
    ];

    // Setiap material dimiliki oleh satu course.
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
