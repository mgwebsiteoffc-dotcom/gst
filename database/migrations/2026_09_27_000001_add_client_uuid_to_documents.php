<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency key for offline billing: each bill created offline carries a
 * client_uuid, so retried syncs never create duplicates.
 *
 * Safe to re-run: skips work that is already done.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('documents') || Schema::hasColumn('documents', 'client_uuid')) {
            return;
        }

        Schema::table('documents', function (Blueprint $t) {
            $t->string('client_uuid', 36)->nullable();
        });

        // Every existing row is NULL here, so the unique index cannot collide.
        if (! Schema::hasIndex('documents', 'documents_tenant_client_uuid_unique')) {
            Schema::table('documents', fn (Blueprint $t) => $t->unique(
                ['tenant_id', 'client_uuid'],
                'documents_tenant_client_uuid_unique'
            ));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'client_uuid')) {
            return;
        }

        Schema::table('documents', function (Blueprint $t) {
            $t->dropUnique('documents_tenant_client_uuid_unique');
            $t->dropColumn('client_uuid');
        });
    }
};
