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
        Schema::create('student_quiz', function (Blueprint $table) {
            $table->id();
            $table->ulid('student_id');
            $table->text('questions');
            $table->text('answers');
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_quiz');
    }
};