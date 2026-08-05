<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('activity_log') || Schema::hasColumn('activity_log', 'event')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $t) {
            $t->string('event')->nullable()->after('batch_uuid');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log') || ! Schema::hasColumn('activity_log', 'event')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $t) {
            $t->dropColumn('event');
        });
    }
};