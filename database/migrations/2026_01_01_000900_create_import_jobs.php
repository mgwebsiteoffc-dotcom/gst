<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('filename');
            $t->string('backup_version')->nullable();
            $t->timestamp('backup_created_at')->nullable();
            $t->string('status')->default('pending'); // pending|analyzing|ready|importing|completed|failed
            $t->json('sections')->nullable();          // detected sections with record counts
            $t->json('mapping')->nullable();           // user-chosen mapping actions
            $t->json('validation')->nullable();        // warnings and errors from validation
            $t->json('summary')->nullable();           // import summary (imported/updated/skipped/failed)
            $t->json('errors')->nullable();            // detailed error log
            $t->integer('total_records')->default(0);
            $t->integer('processed_records')->default(0);
            $t->string('current_section')->nullable();
            $t->integer('duration_seconds')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('file_path')->nullable();       // temp storage path
            $t->timestamps();
            $t->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
