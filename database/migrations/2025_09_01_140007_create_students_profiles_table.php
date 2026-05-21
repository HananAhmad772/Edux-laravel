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
       Schema::create('student_profiles', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('user_id')->unique();
            $t->date('dob')->nullable();
            $t->enum('gender', ['male','female','other'])->nullable();
            $t->string('class_year')->nullable();
            $t->string('institute')->nullable();
            $t->string('major_subject')->nullable();
            $t->text('bio')->nullable();
            $t->timestamps();

            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
