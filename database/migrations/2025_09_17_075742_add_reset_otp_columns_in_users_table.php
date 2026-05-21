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
        Schema::table('users', function (Blueprint $table) {
            $table->string('reset_password_otp', 10)->nullable()->after('remember_token');
            $table->timestamp('reset_password_otp_expiry')->nullable()->after('reset_password_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only attempt to drop columns if they exist to avoid SQL errors during rollback
        $cols = [];
        if (Schema::hasColumn('users', 'reset_password_otp')) {
            $cols[] = 'reset_password_otp';
        }

        if (Schema::hasColumn('users', 'reset_password_otp_expiry')) {
            $cols[] = 'reset_password_otp_expiry';
        }

        if (!empty($cols)) {
            Schema::table('users', function (Blueprint $table) use ($cols) {
                $table->dropColumn($cols);
            });
        }
    }
};
