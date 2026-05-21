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
        Schema::create('roadmap_errors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('roadmap_id')->nullable()->index();
            $table->text('error_message');
            $table->longText('raw_response')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Always add user_id foreign key (users table should exist)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        // Add roadmap_id foreign key only if student_roadmaps table exists
        if (Schema::hasTable('student_roadmaps')) {
            Schema::table('roadmap_errors', function (Blueprint $table) {
                $table->foreign('roadmap_id')->references('id')->on('student_roadmaps')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only attempt to drop foreign keys and table if it exists
        if (Schema::hasTable('roadmap_errors')) {
            Schema::table('roadmap_errors', function (Blueprint $table) {
                // Use try/catch style safety by checking existence via information_schema
                // However, checking for foreign key existence is DB-specific; we'll attempt
                // to drop them if present to avoid migration failures.
                $table->dropForeign(['user_id']);

                // roadmap_id FK may or may not exist depending on migration order
                try {
                    $table->dropForeign(['roadmap_id']);
                } catch (\Exception $e) {
                    // ignore
                }
            });

            Schema::dropIfExists('roadmap_errors');
        }
    }
};

