<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $teams = config('permission.teams');
        $teamKey = config('permission.column_names.team_foreign_key', 'tenant_id');

        Schema::create('permissions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $t->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $t) use ($teams, $teamKey) {
            $t->bigIncrements('id');
            if ($teams) {
                $t->unsignedBigInteger($teamKey)->nullable()->index();
            }
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $teams
                ? $t->unique([$teamKey, 'name', 'guard_name'])
                : $t->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $t) use ($teams, $teamKey) {
            $t->unsignedBigInteger('permission_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->index(['model_id', 'model_type']);
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();

            if ($teams) {
                // Nullable: platform staff hold permissions outside any tenant.
                $t->unsignedBigInteger($teamKey)->nullable()->index();
                $t->unique([$teamKey, 'permission_id', 'model_id', 'model_type'], 'mhp_unique');
            } else {
                $t->primary(['permission_id', 'model_id', 'model_type']);
            }
        });

        Schema::create('model_has_roles', function (Blueprint $t) use ($teams, $teamKey) {
            $t->unsignedBigInteger('role_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->index(['model_id', 'model_type']);
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();

            if ($teams) {
                $t->unsignedBigInteger($teamKey)->nullable()->index();
                $t->unique([$teamKey, 'role_id', 'model_id', 'model_type'], 'mhr_unique');
            } else {
                $t->primary(['role_id', 'model_id', 'model_type']);
            }
        });

        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $t->primary(['permission_id', 'role_id']);
        });

        app('cache')->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        foreach (['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
