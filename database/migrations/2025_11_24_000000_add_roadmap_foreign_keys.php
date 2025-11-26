<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration ensures all foreign keys to student_roadmaps are added
     * if they don't already exist. This handles cases where dependent tables
     * were created before student_roadmaps table.
     */
    public function up(): void
    {
        // Only proceed if student_roadmaps table exists
        if (!Schema::hasTable('student_roadmaps')) {
            return;
        }
        
        $db = Schema::getConnection();
        
        // Helper function to check if foreign key exists
        $hasForeignKey = function($tableName, $columnName) use ($db) {
            try {
                $result = $db->select("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME = 'student_roadmaps'
                ", [$tableName, $columnName]);
                
                return $result[0]->count > 0;
            } catch (\Exception $e) {
                return false;
            }
        };
        
        // Add foreign key to roadmap_errors if table exists and FK doesn't exist
        if (Schema::hasTable('roadmap_errors') && !$hasForeignKey('roadmap_errors', 'roadmap_id')) {
            try {
                Schema::table('roadmap_errors', function (Blueprint $table) {
                    $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, continue
            }
        }
        
        // Add foreign key to daily_progress if table exists and FK doesn't exist
        if (Schema::hasTable('daily_progress') && !$hasForeignKey('daily_progress', 'roadmap_id')) {
            try {
                Schema::table('daily_progress', function (Blueprint $table) {
                    $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, continue
            }
        }
        
        // Add foreign key to user_progress if table exists and FK doesn't exist
        if (Schema::hasTable('user_progress') && !$hasForeignKey('user_progress', 'roadmap_id')) {
            try {
                Schema::table('user_progress', function (Blueprint $table) {
                    $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, continue
            }
        }
        
        // Add foreign key to messages if table exists and FK doesn't exist
        if (Schema::hasTable('messages') && !$hasForeignKey('messages', 'roadmap_id')) {
            try {
                Schema::table('messages', function (Blueprint $table) {
                    $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys if they exist
        $tables = [
            'roadmap_errors' => 'roadmap_errors_roadmap_id_foreign',
            'daily_progress' => 'daily_progress_roadmap_id_foreign',
            'user_progress' => 'user_progress_roadmap_id_foreign',
            'messages' => 'messages_roadmap_id_foreign'
        ];
        
        foreach ($tables as $tableName => $fkName) {
            if (Schema::hasTable($tableName)) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($fkName) {
                        $table->dropForeign([$fkName]);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
        }
    }
};

