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
        Schema::table('personal_access_tokens', function (Blueprint $table) {

            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            $table->string('tokenable_id')->change();
            $table->index(['tokenable_type', 'tokenable_id'], 'tokenable_type_tokenable_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('tokenable_type_tokenable_id_index');
            $table->unsignedBigInteger('tokenable_id')->change();
            $table->index(['tokenable_type', 'tokenable_id'], 'tokenable_type_tokenable_id_index');
        });
    }
};
