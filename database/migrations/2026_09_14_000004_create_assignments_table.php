<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menyimpan tugas yang diberikan pada sebuah mata kuliah.
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->dateTime('due_at');
            $table->unsignedTinyInteger('max_score')->default(100);
            $table->boolean('allow_late')->default(true);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            $table->index(['course_id', 'due_at']);
        });
    }

    public function down(): void
    {
        // Membatalkan pembuatan tabel assignments.
        Schema::dropIfExists('assignments');
    }
};
