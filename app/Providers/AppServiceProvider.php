<?php

namespace App\Providers;

use App\Services\Einvoice\Contracts\EinvoiceGateway;
use App\Services\Einvoice\EinvoiceGatewayResolver;
use App\Services\Einvoice\EinvoicePayloadBuilder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EinvoiceGatewayResolver::class, fn ($app) =>
            new EinvoiceGatewayResolver($app->make(EinvoicePayloadBuilder::class)));

        // Direct injections of the interface get the current tenant's gateway.
        $this->app->bind(EinvoiceGateway::class, fn ($app) =>
            $app->make(EinvoiceGatewayResolver::class)->for());
    }

    public function boot(): void
    {
        // Platform staff bypass every tenant-level permission check.
        Gate::before(fn (User $user) => $user->is_super_admin ? true : null);

        Model::unguard();          // models use $guarded = [] intentionally
        Model::preventLazyLoading(! app()->isProduction());

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
