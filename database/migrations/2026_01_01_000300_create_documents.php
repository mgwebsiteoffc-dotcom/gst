<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_series', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('doc_type');            // invoice|quotation|proforma|delivery_challan|credit_note|debit_note|purchase_order|purchase_bill|bill_of_supply
            $t->string('prefix')->default('');
            $t->string('suffix')->nullable();
            $t->unsignedInteger('next_number')->default(1);
            $t->unsignedTinyInteger('padding')->default(4);
            $t->string('financial_year')->nullable();  // 2026-27
            $t->boolean('is_default')->default(true);
            $t->timestamps();
            $t->unique(['tenant_id', 'doc_type', 'financial_year', 'prefix'], 'series_unique');
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('doc_type')->index();
            $t->string('number')->index();
            $t->date('issue_date');
            $t->date('due_date')->nullable();
            $t->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('documents')->nullOnDelete(); // quote -> invoice chain
            $t->string('status')->default('draft');   // draft|sent|partially_paid|paid|overdue|cancelled

            // GST computation context
            $t->string('supply_type')->default('B2B');    // B2B|B2C|EXPORT|SEZ|DEEMED_EXPORT
            $t->string('place_of_supply_code', 2)->nullable();
            $t->boolean('is_interstate')->default(false);
            $t->boolean('reverse_charge')->default(false);
            $t->boolean('is_export')->default(false);
            $t->boolean('with_payment_of_tax')->default(false);
            $t->string('currency', 3)->default('INR');
            $t->decimal('exchange_rate', 12, 6)->default(1);
            $t->boolean('round_off_enabled')->default(true);

            $t->decimal('subtotal', 15, 2)->default(0);
            $t->decimal('discount_total', 15, 2)->default(0);
            $t->decimal('taxable_value', 15, 2)->default(0);
            $t->decimal('cgst_total', 15, 2)->default(0);
            $t->decimal('sgst_total', 15, 2)->default(0);
            $t->decimal('igst_total', 15, 2)->default(0);
            $t->decimal('cess_total', 15, 2)->default(0);
            $t->decimal('tds_total', 15, 2)->default(0);
            $t->decimal('tcs_total', 15, 2)->default(0);
            $t->decimal('shipping_charge', 15, 2)->default(0);
            $t->decimal('round_off', 15, 2)->default(0);
            $t->decimal('grand_total', 15, 2)->default(0);
            $t->decimal('amount_paid', 15, 2)->default(0);
            $t->decimal('balance_due', 15, 2)->default(0);

            $t->text('notes')->nullable();
            $t->text('terms')->nullable();
            $t->json('meta')->nullable();
            $t->string('source')->default('manual');      // manual|shopify|api|recurring
            $t->string('external_id')->nullable()->index(); // shopify order id
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['tenant_id', 'doc_type', 'number']);
            $t->index(['tenant_id', 'issue_date']);
        });

        Schema::create('document_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedSmallInteger('position')->default(0);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('hsn_sac', 8)->nullable();
            $t->string('uqc', 8)->default('NOS');
            $t->decimal('quantity', 15, 3)->default(1);
            $t->decimal('rate', 15, 4)->default(0);
            $t->decimal('discount_percent', 5, 2)->default(0);
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('taxable_value', 15, 2)->default(0);
            $t->decimal('gst_rate', 5, 2)->default(0);
            $t->decimal('cgst_amount', 15, 2)->default(0);
            $t->decimal('sgst_amount', 15, 2)->default(0);
            $t->decimal('igst_amount', 15, 2)->default(0);
            $t->decimal('cess_rate', 5, 2)->default(0);
            $t->decimal('cess_amount', 15, 2)->default(0);
            $t->decimal('line_total', 15, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->string('direction')->default('in');      // in (receipt) | out (payment)
            $t->decimal('amount', 15, 2);
            $t->date('paid_on');
            $t->string('method')->default('cash');       // cash|bank|upi|card|cheque|gateway
            $t->string('reference')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->string('category')->default('general');
            $t->date('spent_on');
            $t->decimal('amount', 15, 2);
            $t->decimal('gst_amount', 15, 2)->default(0);
            $t->boolean('itc_eligible')->default(false);
            $t->string('reference')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('einvoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->string('irn', 64)->nullable()->unique();
            $t->string('ack_no')->nullable();
            $t->timestamp('ack_date')->nullable();
            $t->longText('signed_invoice')->nullable();
            $t->longText('signed_qr_code')->nullable();
            $t->string('status')->default('pending');    // pending|generated|cancelled|failed
            $t->string('cancel_reason')->nullable();
            $t->json('request_payload')->nullable();
            $t->json('response_payload')->nullable();
            $t->timestamps();
        });

        Schema::create('eway_bills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->string('ewb_no')->nullable()->index();
            $t->timestamp('ewb_date')->nullable();
            $t->timestamp('valid_until')->nullable();
            $t->string('transport_mode')->nullable();    // 1 road 2 rail 3 air 4 ship
            $t->string('vehicle_no')->nullable();
            $t->string('transporter_id')->nullable();
            $t->string('transporter_name')->nullable();
            $t->decimal('distance_km', 8, 2)->default(0);
            $t->string('status')->default('pending');
            $t->json('response_payload')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['eway_bills','einvoices','expenses','payments','document_lines','documents','document_series'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
