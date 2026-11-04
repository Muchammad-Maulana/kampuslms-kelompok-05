<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            // Supports the student scope and newest-course dashboard ordering.
            $table->index(['status', 'created_at'], 'courses_status_created_at_index');
        });

        Schema::table('assignments', function (Blueprint $table): void {
            // Supports published/active assignment filters and deadline ordering.
            $table->index(['status', 'due_at'], 'assignments_status_due_at_index');
        });

        Schema::table('submissions', function (Blueprint $table): void {
            // Supports lecturer reports ordered by submission time.
            $table->index('submitted_at', 'submissions_submitted_at_index');
        });

        Schema::table('grades', function (Blueprint $table): void {
            // Supports the student's latest graded-submission report.
            $table->index('graded_at', 'grades_graded_at_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            // Supports unread-count and notification pagination queries.
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_owner_read_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_owner_read_at_index');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->dropIndex('grades_graded_at_index');
        });

        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropIndex('submissions_submitted_at_index');
        });

        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropIndex('assignments_status_due_at_index');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex('courses_status_created_at_index');
        });
    }
};
