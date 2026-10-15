<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login forensics for support: the IP and device of the last sign-in, so
 * platform staff can confirm "was this really you?" when a customer reports
 * something odd. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $t->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'last_login_user_agent')) {
                $t->string('last_login_user_agent', 255)->nullable()->after('last_login_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach (['last_login_user_agent', 'last_login_ip'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
