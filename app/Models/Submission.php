<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    // Field submission yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'original_name',
        'file_size',
        'note',
        'submitted_at',
        'is_late',
    ];

    // submitted_at menjadi datetime agar waktu pengumpulan mudah diproses.
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    // Setiap submission terkait dengan satu assignment.
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    // Setiap submission dibuat oleh satu user atau mahasiswa.
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user(): BelongsTo
    {
        return $this->student();
    }

    // Satu submission memiliki paling banyak satu grade.
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
