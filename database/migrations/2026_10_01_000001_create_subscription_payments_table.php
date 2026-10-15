<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every subscription payment a business makes, with the GST invoice raised
 * for it — so vendors (and platform staff) can see what was paid and download
 * the bill. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('subscription_payments')) {
            return;
        }

        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();

            $t->string('invoice_number', 40)->index();
            $t->string('plan_name')->nullable();
            $t->string('interval', 10)->default('monthly');

            $t->unsignedInteger('amount_paise')->default(0);   // before tax
            $t->unsignedInteger('gst_paise')->default(0);      // 18% on SaaS
            $t->unsignedInteger('total_paise')->default(0);
            $t->string('currency', 3)->default('INR');
            $t->string('status', 20)->default('paid');         // paid|pending|failed
            $t->string('gateway', 20)->default('razorpay');    // razorpay|manual
            $t->string('gateway_payment_id')->nullable();

            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();

            $t->unique(['tenant_id', 'invoice_number'], 'subscription_payments_tenant_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
