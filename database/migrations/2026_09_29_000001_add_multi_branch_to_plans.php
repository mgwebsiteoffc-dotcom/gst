<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch is a paid capability: only plans with `multi_branch` may create
 * branches / retail stores. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('plans', 'multi_branch')) {
            return;
        }

        Schema::table('plans', function (Blueprint $t) {
            $t->boolean('multi_branch')->default(false)->after('shopify');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'multi_branch')) {
            Schema::table('plans', fn (Blueprint $t) => $t->dropColumn('multi_branch'));
        }
    }
};
