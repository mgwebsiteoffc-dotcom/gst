<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('migration_raw_data', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('import_job_id')->nullable()->constrained()->nullOnDelete();
            $t->string('section');
            $t->string('source_file')->nullable();
            $t->unsignedBigInteger('legacy_id')->nullable();
            $t->json('data');
            $t->string('mapped_model')->nullable();
            $t->unsignedBigInteger('mapped_id')->nullable();
            $t->string('status')->default('imported');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'section']);
            $t->index(['tenant_id', 'section', 'legacy_id']);
            $t->index(['mapped_model', 'mapped_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('migration_raw_data'); }
};