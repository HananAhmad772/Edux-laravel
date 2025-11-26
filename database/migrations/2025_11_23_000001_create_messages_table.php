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
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('roadmap_id')->nullable();
            $table->text('message_body');
            $table->string('role'); // user, assistant, system
            $table->timestamps();
            
            // Always add user_id foreign key (users table should exist)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        // Add roadmap_id foreign key only if student_roadmaps table exists
        if (Schema::hasTable('student_roadmaps')) {
            Schema::table('messages', function (Blueprint $table) {
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
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['roadmap_id']);
            });
        }
        
        Schema::dropIfExists('messages');
    }
};