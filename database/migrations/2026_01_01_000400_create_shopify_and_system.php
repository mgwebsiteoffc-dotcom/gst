<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopify_stores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('shop_domain')->unique();          // acme.myshopify.com
            $t->text('access_token');                     // encrypted cast
            $t->string('scopes')->nullable();
            $t->string('api_version')->default('2026-01');
            $t->boolean('auto_create_invoice')->default(true);
            $t->boolean('sync_products')->default(true);
            $t->boolean('prices_include_tax')->default(true);
            $t->string('default_hsn', 8)->nullable();
            $t->decimal('default_gst_rate', 5, 2)->default(18);
            $t->string('seller_state_code', 2)->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });

        Schema::create('shopify_sync_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shopify_store_id')->constrained()->cascadeOnDelete();
            $t->string('topic');                          // orders/create, refunds/create ...
            $t->string('external_id')->nullable()->index();
            $t->string('status')->default('received');    // received|processed|failed|skipped
            $t->text('message')->nullable();
            $t->json('payload')->nullable();
            $t->timestamps();
        });

        Schema::create('jobs', function (Blueprint $t) {
            $t->id();
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('uuid')->unique();
            $t->text('connection');
            $t->text('queue');
            $t->longText('payload');
            $t->longText('exception');
            $t->timestamp('failed_at')->useCurrent();
        });

        Schema::create('cache', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration');
        });
    }

    public function down(): void
    {
        foreach (['cache','failed_jobs','jobs','shopify_sync_logs','shopify_stores'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
