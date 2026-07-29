<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $t->string('title');
            $t->string('slug')->unique();
            $t->string('excerpt', 500)->nullable();
            $t->longText('body')->nullable();          // rich HTML from the editor
            $t->string('cover_image')->nullable();
            $t->string('cover_alt')->nullable();

            // SEO / AEO
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 300)->nullable();
            $t->string('focus_keyword')->nullable();
            $t->string('canonical_url')->nullable();
            $t->boolean('noindex')->default(false);
            $t->json('faqs')->nullable();              // [{question, answer}] -> FAQPage schema

            $t->string('status')->default('draft');    // draft|published
            $t->timestamp('published_at')->nullable();
            $t->unsignedInteger('reading_minutes')->default(1);
            $t->unsignedInteger('views')->default(0);

            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'published_at']);
        });

        // Reusable FAQs for marketing pages (pricing, features, home).
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('page')->default('home')->index();  // home|pricing|features|gst|shopify
            $t->string('question');
            $t->text('answer');
            $t->unsignedSmallInteger('position')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('business')->nullable();
            $t->string('subject')->nullable();
            $t->text('message')->nullable();
            $t->string('source')->default('contact');   // contact|demo|newsletter
            $t->string('status')->default('new');       // new|contacted|closed
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['leads', 'faqs', 'posts', 'post_categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
