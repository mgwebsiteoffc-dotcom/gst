<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch / multi-store: a business (X) can own branches (A, B, …).
 *
 * Each branch is a full tenant of its own — own GSTIN, invoice series,
 * customers, items and reports — but bills to the parent's subscription and
 * is switched between from one login. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('tenants', 'parent_tenant_id')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $t) {
            $t->foreignId('parent_tenant_id')->nullable()->after('id')
                ->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tenants', 'parent_tenant_id')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_tenant_id');
        });
    }
};
