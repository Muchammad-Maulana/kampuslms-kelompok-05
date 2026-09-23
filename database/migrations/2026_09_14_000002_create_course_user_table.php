<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel pivot untuk hubungan many-to-many course dan mahasiswa.
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            // Pendaftaran ikut terhapus jika course induknya dihapus.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // Data pivot ikut terhapus jika user induknya dihapus.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            // Mencegah mahasiswa terdaftar dua kali pada course yang sama.
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        // Membalikkan pembuatan tabel pivot.
        Schema::dropIfExists('course_user');
    }
};
