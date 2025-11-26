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
        Schema::create('daily_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('roadmap_id')->nullable()->index();
            $table->date('progress_date')->index(); // Day-wise storage
            $table->string('step_name')->nullable(); // e.g., "Week 1–2"
            $table->unsignedTinyInteger('topic_index')->nullable(); // 1-6
            $table->string('topic_name')->nullable(); // Topic from roadmap
            $table->boolean('topic_completed')->default(false);
            $table->integer('xp_earned')->default(0); // XP earned on this day
            $table->integer('time_spent_minutes')->default(0); // Time spent in minutes
            $table->json('completed_tasks')->nullable(); // Array of completed task IDs/names
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Always add user_id foreign key (users table should exist)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Ensure one record per user per day
            $table->unique(['user_id', 'progress_date']);
        });
        
        // Add roadmap_id foreign key only if student_roadmaps table exists
        if (Schema::hasTable('student_roadmaps')) {
            Schema::table('daily_progress', function (Blueprint $table) {
                $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        if (Schema::hasTable('daily_progress')) {
            Schema::table('daily_progress', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
        }
        
        Schema::dropIfExists('daily_progress');
    }
};

