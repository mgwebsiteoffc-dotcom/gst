<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table used by spatie/laravel-activitylog (installed via composer). Guarded so
 * it stays a no-op if the package's own migration is ever published & run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('activity_log')) {
            return;
        }

        Schema::create('activity_log', function (Blueprint $t) {
            $t->id();
            $t->string('log_name')->nullable()->index();
            $t->text('description');
            $t->nullableMorphs('subject');        // subject_type + subject_id
            $t->nullableMorphs('causer');         // causer_type + causer_id
            $t->json('properties')->nullable();   // {attributes: {...}, old: {...}}
            $t->uuid('batch_uuid')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
