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
        Schema::table('student_quiz', function (Blueprint $table) {
            $table->longText('questions')->change();
            $table->longText('answers')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_quiz', function (Blueprint $table) {
            $table->text('questions')->change();
            $table->text('answers')->change();
        });
    }
};