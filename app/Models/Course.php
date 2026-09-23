<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

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
