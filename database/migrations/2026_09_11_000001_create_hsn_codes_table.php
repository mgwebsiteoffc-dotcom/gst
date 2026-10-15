<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $t) {
            $t->id();
            $t->string('code', 16)->unique();
            $t->string('description', 255);
            $t->string('type', 8)->default('HSN'); // HSN|goods, SAC|services
            $t->decimal('gst_rate', 5, 2)->default(0);
            $t->timestamps();
            $t->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
