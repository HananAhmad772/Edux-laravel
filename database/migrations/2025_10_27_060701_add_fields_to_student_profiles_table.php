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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('current_position')->nullable();
            $table->string('specialization_field')->nullable();
            $table->text('preferred_technologies')->nullable();
            $table->string('current_skill_level')->nullable();
            $table->string('main_goal')->nullable();
            $table->string('time_per_week')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'current_position',
                'specialization_field',
                'preferred_technologies',
                'current_skill_level',
                'main_goal',
                'time_per_week'
            ]);
        });
    }
};