<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * TenantContext and spatie's team id are process-global. Without an explicit
     * reset they leak between tests and cause order-dependent failures.
     */
    protected function tearDown(): void
    {
        \App\Support\TenantContext::forget();

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(null);
        }

        parent::tearDown();
    }
}
