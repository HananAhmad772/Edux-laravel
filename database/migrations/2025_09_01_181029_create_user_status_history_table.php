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
        Schema::create('user_status_history', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('user_id');
            $t->enum('from_status', ['pending','under_review','approved','rejected','locked'])->nullable();
            $t->enum('to_status',   ['pending','under_review','approved','rejected','locked']);
            $t->text('reason')->nullable();
            $t->ulid('changed_by')->nullable();
            $t->timestamps();

            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_status_history');
    }
};
