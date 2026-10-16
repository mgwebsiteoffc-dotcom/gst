<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distributor applications collect a few extra details that the channel and
 * affiliate forms do not ask for. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $t) {
            if (! Schema::hasColumn('partners', 'territory')) {
                $t->string('territory', 150)->nullable()->after('city');
            }

            if (! Schema::hasColumn('partners', 'current_business')) {
                $t->string('current_business', 150)->nullable()->after('territory');
            }

            if (! Schema::hasColumn('partners', 'years_experience')) {
                $t->unsignedTinyInteger('years_experience')->nullable()->after('current_business');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $t) {
            foreach (['years_experience', 'current_business', 'territory'] as $column) {
                if (Schema::hasColumn('partners', $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
