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
        Schema::create('users_attachments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->morphs('attachable'); // attachable_type, attachable_id
            $t->string('category');
            $t->string('path');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_attachments');
    }
};
