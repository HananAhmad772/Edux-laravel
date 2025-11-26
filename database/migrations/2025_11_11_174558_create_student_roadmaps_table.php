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
     * Note: This is the original create migration.
     * If drop_and_recreate migration exists, it will handle the drop.
     */
    public function down(): void
    {
        // Only drop if table exists and no dependent tables exist
        // Check if dependent tables exist first
        $hasDependents = Schema::hasTable('roadmap_errors') || 
                        Schema::hasTable('daily_progress') || 
                        Schema::hasTable('user_progress') || 
                        Schema::hasTable('messages');
        
        if (!$hasDependents && Schema::hasTable('student_roadmaps')) {
            Schema::dropIfExists('student_roadmaps');
        }
    }
};