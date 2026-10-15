<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('endpoint', 500)->unique();
            $t->string('p256dh', 200);
            $t->string('auth', 50);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
