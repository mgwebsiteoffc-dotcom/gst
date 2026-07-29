<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    protected static ?Tenant $tenant = null;

    public static function set(?Tenant $tenant): void { static::$tenant = $tenant; }
    public static function get(): ?Tenant { return static::$tenant; }
    public static function id(): ?int { return static::$tenant?->id; }
    public static function forget(): void { static::$tenant = null; }

    /** Run a callback for a specific tenant (queues, webhooks, console). */
    public static function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = static::$tenant;
        static::$tenant = $tenant;
        try { return $callback($tenant); } finally { static::$tenant = $previous; }
    }
}
