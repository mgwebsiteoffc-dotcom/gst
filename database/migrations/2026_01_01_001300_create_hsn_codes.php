<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $t) {   // platform-wide master, managed by super admin
            $t->id();
            $t->string('code', 8)->unique();
            $t->string('description');
            $t->string('type')->default('goods');               // goods (HSN) | service (SAC)
            $t->decimal('gst_rate', 5, 2)->nullable();           // suggested slab, item can still override
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
