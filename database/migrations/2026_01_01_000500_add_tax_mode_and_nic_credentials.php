<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            // 'gst'     => full GST tax invoice workflow (registered dealers)
            // 'non_gst' => plain invoice, no tax columns (unregistered / freelancers / exempt)
            $t->string('tax_mode')->default('gst')->after('registration_type');
            $t->boolean('allow_both_modes')->default(true)->after('tax_mode');

            // Per-tenant NIC / GSP credentials (encrypted at rest).
            $t->string('einvoice_driver')->nullable()->after('einvoice_enabled'); // null = app default
            $t->text('nic_username')->nullable();
            $t->text('nic_password')->nullable();
            $t->text('nic_client_id')->nullable();
            $t->text('nic_client_secret')->nullable();
            $t->boolean('nic_sandbox')->default(true);
            $t->timestamp('nic_verified_at')->nullable();
        });

        Schema::table('documents', function (Blueprint $t) {
            $t->string('tax_mode')->default('gst')->after('doc_type')->index();
        });

        Schema::table('items', function (Blueprint $t) {
            $t->boolean('is_taxable')->default(true)->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('items', fn (Blueprint $t) => $t->dropColumn('is_taxable'));
        Schema::table('documents', fn (Blueprint $t) => $t->dropColumn('tax_mode'));
        Schema::table('tenants', function (Blueprint $t) {
            $t->dropColumn(['tax_mode', 'allow_both_modes', 'einvoice_driver', 'nic_username',
                'nic_password', 'nic_client_id', 'nic_client_secret', 'nic_sandbox', 'nic_verified_at']);
        });
    }
};
