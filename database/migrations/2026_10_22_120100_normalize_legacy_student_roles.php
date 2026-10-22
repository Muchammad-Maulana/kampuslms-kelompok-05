<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'student')
            ->update(['role' => 'mahasiswa']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'mahasiswa')
            ->update(['role' => 'student']);
    }
};