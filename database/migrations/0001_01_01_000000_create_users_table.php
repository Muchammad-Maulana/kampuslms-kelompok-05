<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Membuat tabel akun untuk admin, dosen, dan mahasiswa.
        Schema::create('users', function (Blueprint $table) {
            // Primary key otomatis untuk setiap user.
            $table->id();
            $table->string('name');
            // Email harus unik agar satu email hanya dimiliki satu akun.
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            // Password akan di-hash melalui casting pada model User.
            $table->string('password');
            $table->enum('role', ['admin', 'dosen', 'mahasiswa'])->default('mahasiswa')->index();
            $table->string('nim_nip')->nullable()->unique();
            // Token bawaan Laravel untuk fitur remember me.
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel bawaan Laravel untuk proses reset password.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Tabel bawaan Laravel untuk menyimpan session aplikasi.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Membalikkan up() agar migration dapat di-rollback atau di-refresh.
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
