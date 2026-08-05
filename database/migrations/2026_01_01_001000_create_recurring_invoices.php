<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->string('doc_type')->default('invoice');         // invoice|bill_of_supply
            $t->string('tax_mode')->default('gst');             // gst|non_gst

            // Schedule
            $t->string('frequency')->default('monthly');        // daily|weekly|monthly|quarterly|half_yearly|yearly
            $t->unsignedSmallInteger('interval_count')->default(1); // every N frequencies
            $t->date('starts_on');
            $t->date('next_run_on')->index();
            $t->date('ends_on')->nullable();
            $t->unsignedInteger('max_occurrences')->nullable();
            $t->unsignedInteger('generated_count')->default(0);
            $t->string('status')->default('active')->index();   // active|paused|completed|cancelled

            // Generated document behaviour
            $t->boolean('auto_finalize')->default(true);        // true = 'sent', false = 'draft'
            $t->unsignedSmallInteger('due_days')->nullable();   // override payment terms (days after issue)
            $t->json('template');                               // snapshot of header flags + lines
            $t->timestamp('last_generated_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'status', 'next_run_on']);
        });

        // Traceability: which schedule produced this document.
        Schema::table('documents', function (Blueprint $t) {
            $t->foreignId('recurring_invoice_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('recurring_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            $t->dropConstrainedForeignId('recurring_invoice_id');
        });
        Schema::dropIfExists('recurring_invoices');
    }
};
