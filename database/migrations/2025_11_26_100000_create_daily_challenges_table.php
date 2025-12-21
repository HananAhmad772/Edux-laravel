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
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('roadmap_id')->nullable()->index();
            $table->date('challenge_date')->index();
            $table->string('step_name')->nullable();
            $table->unsignedTinyInteger('topic_index')->nullable();
            $table->string('topic_name')->nullable();
            $table->text('challenge_description');
            $table->json('challenge_data')->nullable(); // Structured challenge data
            $table->text('student_submission')->nullable(); // Student's answer
            $table->text('ai_feedback')->nullable(); // AI's feedback on submission
            $table->integer('points_earned')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
            
            // Ensure one challenge per user per day
            $table->unique(['user_id', 'challenge_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_challenges');
    }
};