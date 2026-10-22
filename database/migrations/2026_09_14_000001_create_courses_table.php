<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Course berarti mata kuliah pada aplikasi KampusLMS.
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // Kode mata kuliah tidak boleh duplikat.
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sks');
            // Dosen yang masih mengampu course tidak boleh dihapus otomatis.
            $table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
            // Status dibatasi agar data hanya memiliki tiga keadaan bisnis.
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Menghapus tabel courses saat migration dibatalkan.
        Schema::dropIfExists('courses');
    }
};
