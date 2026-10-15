<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency key for offline billing: each bill created offline carries a
 * client_uuid, so retried syncs never create duplicates.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            $t->string('client_uuid', 36)->nullable()->after('external_id');
            $t->unique(['tenant_id', 'client_uuid'], 'documents_tenant_client_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            $t->dropUnique('documents_tenant_client_uuid_unique');
            $t->dropColumn('client_uuid');
        });
    }
};
