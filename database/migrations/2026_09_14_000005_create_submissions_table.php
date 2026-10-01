<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menyimpan pengumpulan tugas dari mahasiswa.
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            // Submission ikut terhapus jika assignment induknya dihapus.
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size');
            $table->text('note')->nullable();
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->timestamps();

            // Satu mahasiswa hanya boleh submit sekali untuk satu assignment.
            $table->unique(['assignment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        // Membatalkan pembuatan tabel submissions.
        Schema::dropIfExists('submissions');
    }
};
