<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Course extends Model
{
    use HasFactory;

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query,
            'dosen' => $query->where('lecturer_id', $user->id),
            'mahasiswa' => $query->whereHas('students', fn (Builder $students) => $students->whereKey($user->id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    // Field yang boleh diisi saat course dibuat atau diperbarui.
    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'lecturer_id',
        'status',
    ];

    // Status disimpan sebagai string sesuai enum pada migration.
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    // Setiap course memiliki satu dosen melalui courses.lecturer_id.
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    // Course memiliki banyak mahasiswa melalui tabel pivot course_user.
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    // Satu course dapat memiliki banyak material.
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    // Satu course dapat memiliki banyak assignment.
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
