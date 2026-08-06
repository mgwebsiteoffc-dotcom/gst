<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only 'invoice' ever had two distinct default prefixes (INV vs BILL) depending on
     * tax mode — every other doc type shared one prefix regardless of mode, so this
     * column is populated for invoices only; everything else stays null (shared series).
     */
    public function up(): void
    {
        Schema::table('document_series', function (Blueprint $t) {
            $t->string('tax_mode')->nullable()->after('doc_type');
        });

        DB::table('document_series')
            ->where('doc_type', 'invoice')
            ->where('prefix', 'like', 'BILL%')
            ->update(['tax_mode' => 'non_gst']);

        DB::table('document_series')
            ->where('doc_type', 'invoice')
            ->where('prefix', 'not like', 'BILL%')
            ->update(['tax_mode' => 'gst']);
    }

    public function down(): void
    {
        Schema::table('document_series', function (Blueprint $t) {
            $t->dropColumn('tax_mode');
        });
    }
};
