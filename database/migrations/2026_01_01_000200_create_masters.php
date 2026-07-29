<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $t) {   // clients + vendors
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('customer');           // customer|vendor|both
            $t->string('name');
            $t->string('contact_person')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('gstin', 15)->nullable()->index();
            $t->string('pan', 10)->nullable();
            $t->string('gst_treatment')->default('unregistered'); // regular|composition|unregistered|consumer|overseas|sez
            $t->string('billing_address')->nullable();
            $t->string('billing_city')->nullable();
            $t->string('billing_state')->nullable();
            $t->string('billing_state_code', 2)->nullable();
            $t->string('billing_pincode', 6)->nullable();
            $t->string('billing_country', 2)->default('IN');
            $t->json('shipping_address')->nullable();
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->unsignedSmallInteger('credit_days')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['tenant_id', 'type']);
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('sku')->nullable()->index();
            $t->string('barcode')->nullable();
            $t->text('description')->nullable();
            $t->string('type')->default('goods');              // goods|service
            $t->string('hsn_sac', 8)->nullable()->index();
            $t->string('uqc', 8)->default('NOS');              // GST unit code
            $t->decimal('sale_price', 15, 4)->default(0);
            $t->decimal('purchase_price', 15, 4)->default(0);
            $t->decimal('gst_rate', 5, 2)->default(18);
            $t->decimal('cess_rate', 5, 2)->default(0);
            $t->boolean('price_inclusive_tax')->default(false);
            $t->boolean('track_inventory')->default(true);
            $t->decimal('opening_stock', 15, 3)->default(0);
            $t->decimal('reorder_level', 15, 3)->default(0);
            $t->string('shopify_product_id')->nullable()->index();
            $t->string('shopify_variant_id')->nullable()->index();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('state_code', 2)->nullable();
            $t->string('address')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $t->string('direction');                            // in|out
            $t->decimal('quantity', 15, 3);
            $t->decimal('rate', 15, 4)->default(0);
            $t->nullableMorphs('source');                       // invoice, purchase bill, adjustment
            $t->date('moved_on');
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'item_id', 'moved_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('items');
        Schema::dropIfExists('parties');
    }
};
