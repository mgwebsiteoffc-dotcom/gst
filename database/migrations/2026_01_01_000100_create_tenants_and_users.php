<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('legal_name')->nullable();
            $t->string('gstin', 15)->nullable()->index();
            $t->string('pan', 10)->nullable();
            $t->string('state_code', 2)->nullable();       // GST place-of-supply origin
            $t->string('registration_type')->default('regular'); // regular|composition|unregistered|sez
            $t->boolean('einvoice_enabled')->default(false);
            $t->boolean('ewaybill_enabled')->default(false);
            $t->string('country', 2)->default('IN');
            $t->string('currency', 3)->default('INR');
            $t->string('timezone')->default('Asia/Kolkata');
            $t->string('financial_year_start')->default('04-01');
            $t->json('branding')->nullable();               // logo, colors, signature
            $t->json('settings')->nullable();
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('phone')->nullable();
            $t->boolean('is_super_admin')->default(false);
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedInteger('price_paise');            // monthly price in paise
            $t->unsignedInteger('yearly_price_paise')->nullable();
            $t->unsignedInteger('invoice_limit')->nullable(); // null = unlimited
            $t->unsignedInteger('user_limit')->default(1);
            $t->boolean('einvoice')->default(false);
            $t->boolean('shopify')->default(false);
            $t->json('features')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained();
            $t->string('gateway')->default('razorpay');
            $t->string('gateway_subscription_id')->nullable();
            $t->string('status')->default('trialing');      // trialing|active|past_due|canceled
            $t->string('interval')->default('monthly');
            $t->timestamp('current_period_end')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
