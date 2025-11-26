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
        Schema::table('student_roadmaps', function (Blueprint $table) {
            // Change roadmap_json from JSON to longText to handle large structured data
            $table->longText('roadmap_json')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_roadmaps', function (Blueprint $table) {
            // Revert roadmap_json back to JSON
            $table->json('roadmap_json')->nullable()->change();
        });
    }
};