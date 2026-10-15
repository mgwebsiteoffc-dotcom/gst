<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * HSN/SAC master table.
 *
 * The table may already exist when this migration runs: many installs import
 * database/sql/hsn_codes_2026.sql directly in phpMyAdmin first, and some had
 * their own hsn_codes table before. In both cases we leave the data alone and
 * only make sure the `code` unique index exists (the seeder upserts on it).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hsn_codes')) {
            $this->ensureCodeIsUnique();

            return;
        }

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

    /** HsnCodeSeeder::upsert() needs a unique key on `code`. */
    protected function ensureCodeIsUnique(): void
    {
        if (Schema::hasIndex('hsn_codes', 'hsn_codes_code_unique')
            || Schema::hasIndex('hsn_codes', ['code'], 'unique')) {
            return;
        }

        try {
            Schema::table('hsn_codes', fn (Blueprint $t) => $t->unique('code'));
        } catch (\Throwable $e) {
            // Duplicate codes already present — leave the data untouched.
            Log::warning('Could not add unique index on hsn_codes.code: '.$e->getMessage());
        }
    }
};
