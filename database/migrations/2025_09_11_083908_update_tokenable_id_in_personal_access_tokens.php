<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
         // Only drop the index if it exists to avoid errors
         $indexExists = DB::select("SHOW INDEX FROM personal_access_tokens WHERE Key_name = ?", ['tokenable_type_tokenable_id_index']);

         if (!empty($indexExists)) {
             Schema::table('personal_access_tokens', function (Blueprint $table) {
                 $table->dropIndex('tokenable_type_tokenable_id_index');
             });
         }

         // Only convert back to unsignedBigInteger if all tokenable_id values are numeric
         $hasNonNumeric = DB::table('personal_access_tokens')
            ->whereRaw("tokenable_id REGEXP '[^0-9]'")
            ->exists();

         if (!$hasNonNumeric) {
             Schema::table('personal_access_tokens', function (Blueprint $table) {
                 $table->unsignedBigInteger('tokenable_id')->change();
             });
         }

         // Re-create the index if it was present originally
         if (!empty($indexExists)) {
             Schema::table('personal_access_tokens', function (Blueprint $table) {
                 $table->index(['tokenable_type', 'tokenable_id'], 'tokenable_type_tokenable_id_index');
             });
         }
    }
};
