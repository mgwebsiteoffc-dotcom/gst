<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Commission structures the super admin defines and assigns to partners.
        Schema::create('commission_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('partner_type');                       // channel|affiliate
            $t->string('model')->default('percentage');       // percentage|flat|hybrid
            $t->decimal('percentage', 5, 2)->default(20);     // % of each payment
            $t->unsignedInteger('flat_paise')->default(0);    // fixed amount per conversion
            $t->unsignedSmallInteger('recurring_months')->default(12); // 0 = first payment only
            $t->unsignedSmallInteger('cookie_days')->default(60);      // attribution window
            $t->unsignedInteger('min_payout_paise')->default(100000);  // ₹1,000
            $t->unsignedSmallInteger('hold_days')->default(30);        // clawback window
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('partners', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('commission_plan_id')->nullable()->constrained()->nullOnDelete();

            $t->string('type')->default('affiliate');         // channel|affiliate
            $t->string('name');
            $t->string('company')->nullable();
            $t->string('email')->unique();
            $t->string('phone')->nullable();
            $t->string('referral_code', 32)->unique();

            // Channel-partner specifics
            $t->string('gstin', 15)->nullable();
            $t->string('pan', 10)->nullable();
            $t->string('city')->nullable();
            $t->string('state_code', 2)->nullable();
            $t->string('tier')->default('bronze');            // bronze|silver|gold

            // Influencer specifics
            $t->string('website')->nullable();
            $t->json('social')->nullable();                   // {youtube, instagram, x}
            $t->unsignedInteger('audience_size')->default(0);
            $t->text('pitch')->nullable();                    // why they applied

            // Payout details
            $t->string('bank_account')->nullable();
            $t->string('bank_ifsc')->nullable();
            $t->string('upi')->nullable();

            $t->string('status')->default('pending');         // pending|approved|suspended|rejected
            $t->text('admin_note')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();
            $t->softDeletes();
            $t->index(['type', 'status']);
        });

        // Raw click tracking, used for conversion-rate reporting.
        Schema::create('referral_clicks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->string('landing_page')->nullable();
            $t->string('utm_source')->nullable();
            $t->string('utm_campaign')->nullable();
            $t->string('referrer')->nullable();
            $t->string('ip_hash', 64)->nullable();            // hashed, never raw IP
            $t->string('visitor_token', 64)->nullable()->index();
            $t->timestamps();
            $t->index(['partner_id', 'created_at']);
        });

        // A signed-up business attributed to a partner.
        Schema::create('referrals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('trial');           // trial|converted|churned
            $t->timestamp('converted_at')->nullable();
            $t->unsignedInteger('lifetime_value_paise')->default(0);
            $t->timestamps();
            $t->unique('tenant_id');                          // a business has one referrer, forever
        });

        // One row per commissionable event.
        Schema::create('commissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->foreignId('referral_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('payout_id')->nullable();

            $t->unsignedInteger('base_paise');                // the payment we earned on
            $t->decimal('rate', 5, 2)->default(0);
            $t->unsignedInteger('amount_paise');              // commission owed
            $t->unsignedSmallInteger('month_number')->default(1); // 1st, 2nd... recurring month

            $t->string('status')->default('pending');         // pending|approved|paid|reversed
            $t->timestamp('available_at')->nullable();        // after the hold period
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['partner_id', 'status']);
        });

        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->string('reference')->unique();
            $t->unsignedInteger('amount_paise');
            $t->unsignedSmallInteger('commission_count')->default(0);
            $t->string('method')->default('bank');            // bank|upi
            $t->string('status')->default('pending');         // pending|processing|paid|failed
            $t->string('utr')->nullable();                    // bank transaction reference
            $t->text('note')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('commissions', function (Blueprint $t) {
            $t->foreign('payout_id')->references('id')->on('payouts')->nullOnDelete();
        });

        // Super-admin controls over a tenant account.
        Schema::table('tenants', function (Blueprint $t) {
            $t->string('account_status')->default('active')->after('slug'); // active|suspended|closed
            $t->string('suspension_reason')->nullable()->after('account_status');
            $t->timestamp('suspended_at')->nullable()->after('suspension_reason');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('is_super_admin');
            $t->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_active', 'last_login_at']));
        Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn(['account_status', 'suspension_reason', 'suspended_at']));

        foreach (['payouts', 'commissions', 'referrals', 'referral_clicks', 'partners', 'commission_plans'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
