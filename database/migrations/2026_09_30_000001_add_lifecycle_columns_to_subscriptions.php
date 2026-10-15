<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription lifecycle timestamps: when the paid period began and when the
 * business cancelled (access still runs to current_period_end). Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            if (! Schema::hasColumn('subscriptions', 'started_at')) {
                $t->timestamp('started_at')->nullable()->after('current_period_end');
            }

            if (! Schema::hasColumn('subscriptions', 'canceled_at')) {
                $t->timestamp('canceled_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            foreach (['canceled_at', 'started_at'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
