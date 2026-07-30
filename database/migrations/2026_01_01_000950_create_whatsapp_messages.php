<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->nullableMorphs('messageable');         // Document, Party, etc.
            $t->string('to');                          // recipient phone number
            $t->string('type')->default('text');       // text|template|document|media
            $t->string('template_name')->nullable();   // Meta-approved template
            $t->json('template_params')->nullable();   // template component params
            $t->text('body')->nullable();              // text message body
            $t->string('media_url')->nullable();       // PDF or media attachment
            $t->string('media_filename')->nullable();
            $t->string('status')->default('queued');   // queued|sent|delivered|read|failed
            $t->string('whatsapp_message_id')->nullable()->index(); // Meta's message ID
            $t->text('error_message')->nullable();
            $t->json('response')->nullable();          // raw API response
            $t->integer('retry_count')->default(0);
            $t->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
