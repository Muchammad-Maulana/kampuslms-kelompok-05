<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menyimpan nilai dan feedback untuk sebuah submission.
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('graded_by')->constrained('users')->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at');
            $table->timestamps();

            // Satu submission hanya boleh memiliki satu grade.
            $table->unique('submission_id');
        });
    }

    public function down(): void
    {
        // Membatalkan pembuatan tabel grades.
        Schema::dropIfExists('grades');
    }
};
