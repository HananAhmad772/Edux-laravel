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
        // Only add columns if table exists and columns don't exist
        if (Schema::hasTable('student_roadmaps')) {
            Schema::table('student_roadmaps', function (Blueprint $table) {
                if (!Schema::hasColumn('student_roadmaps', 'roadmap_json')) {
                    $table->json('roadmap_json')->nullable()->after('roadmap_content');
                }
                if (!Schema::hasColumn('student_roadmaps', 'status')) {
                    $table->string('status')->default('active')->after('roadmap_json');
                    $table->index('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop columns if table exists and columns exist
        if (Schema::hasTable('student_roadmaps')) {
            Schema::table('student_roadmaps', function (Blueprint $table) {
                if (Schema::hasColumn('student_roadmaps', 'roadmap_json')) {
                    $table->dropColumn('roadmap_json');
                }
                if (Schema::hasColumn('student_roadmaps', 'status')) {
                    // Drop index first if it exists
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $indexesFound = $sm->listTableIndexes('student_roadmaps');
                    if (isset($indexesFound['student_roadmaps_status_index'])) {
                        $table->dropIndex('student_roadmaps_status_index');
                    }
                    $table->dropColumn('status');
                }
            });
        }
    }
};

