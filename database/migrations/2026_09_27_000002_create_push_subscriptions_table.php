<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Web Push subscriptions (PWA payment reminders). Safe to re-run.
 *
 * The unique index on `endpoint` is added separately so an old MySQL that
 * refuses long unique keys still gets the table created.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::create('push_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('endpoint', 500);
            $t->string('p256dh', 200);
            $t->string('auth', 50);
            $t->timestamps();
        });

        try {
            Schema::table('push_subscriptions', fn (Blueprint $t) => $t->unique('endpoint'));
        } catch (\Throwable $e) {
            // PushController still de-duplicates with updateOrCreate().
            Log::warning('Could not add unique index on push_subscriptions.endpoint: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
