<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: This migration drops and recreates the student_roadmaps table.
     * It should only run if the table structure needs to be changed.
     * 
     * IMPORTANT: This migration is now safe - it only runs if the table exists
     * and doesn't have the new columns yet. If the table already has roadmap_json
     * and status columns, this migration will be skipped.
     */
    public function up(): void
    {
        // Only proceed if the table exists
        if (!Schema::hasTable('student_roadmaps')) {
            // Table doesn't exist, let the create migration handle it
            return;
        }
        
        // Check if table already has the new columns
        if (Schema::hasColumn('student_roadmaps', 'roadmap_json') && 
            Schema::hasColumn('student_roadmaps', 'status')) {
            // Table already has the correct structure, skip this migration
            return;
        }
        
        // Drop dependent tables first to avoid foreign key constraint errors
        // Only drop if they exist (they might not exist yet if this runs early)
        if (Schema::hasTable('roadmap_errors')) {
            Schema::table('roadmap_errors', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('roadmap_errors');
        }
        
        if (Schema::hasTable('daily_progress')) {
            Schema::table('daily_progress', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('daily_progress');
        }
        
        if (Schema::hasTable('user_progress')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('user_progress');
        }
        
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('messages');
        }
        
        // Drop the table if it exists
        Schema::dropIfExists('student_roadmaps');
        
        // Recreate the table with correct schema
        Schema::create('student_roadmaps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->longText('roadmap_content');
            $table->json('roadmap_json')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     * Note: This migration drops and recreates the table.
     * On rollback, we restore the original structure (without roadmap_json and status).
     */
    public function down(): void
    {
        // Only proceed if the table exists
        if (!Schema::hasTable('student_roadmaps')) {
            return;
        }
        
        // Drop dependent tables first to avoid foreign key constraint errors
        // Only drop if they exist
        if (Schema::hasTable('roadmap_errors')) {
            Schema::table('roadmap_errors', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('roadmap_errors');
        }
        
        if (Schema::hasTable('daily_progress')) {
            Schema::table('daily_progress', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('daily_progress');
        }
        
        if (Schema::hasTable('user_progress')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('user_progress');
        }
        
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
            Schema::dropIfExists('messages');
        }
        
        // Drop the table
        Schema::dropIfExists('student_roadmaps');
        
        // Recreate with original structure (without roadmap_json and status)
        Schema::create('student_roadmaps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->longText('roadmap_content');
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};