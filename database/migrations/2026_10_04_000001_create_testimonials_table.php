<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer reviews shown on the marketing site. Managed from the admin panel,
 * so the numbers on the homepage come from real, approved testimonials rather
 * than hardcoded claims. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('testimonials')) {
            return;
        }

        Schema::create('testimonials', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('business')->nullable();       // "Sharma Kirana Store"
            $t->string('city')->nullable();
            $t->string('business_type')->nullable();  // retailer|wholesaler|ca|manufacturer|service
            $t->text('quote');
            $t->unsignedTinyInteger('rating')->default(5);
            $t->string('photo')->nullable();          // path on the public disk
            $t->boolean('featured')->default(false);  // show in the hero strip
            $t->unsignedSmallInteger('position')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
