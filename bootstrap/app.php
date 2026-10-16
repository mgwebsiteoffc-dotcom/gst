<?php

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\RequireTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SetLocale::class, IdentifyTenant::class, CaptureReferral::class, SecurityHeaders::class]);

        // The referral cookie carries a public partner code, never anything secret.
        // Leaving it unencrypted lets edge caches and analytics read it too.
        $middleware->encryptCookies(except: [
            \App\Services\Partner\ReferralService::COOKIE,
            'bb_visitor',
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->api(append: [IdentifyTenant::class]);

        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'subscribed' => EnsureSubscriptionActive::class,
            'shopify.webhook' => VerifyShopifyWebhook::class,
            'super-admin' => EnsureSuperAdmin::class,
            'tenant.required' => RequireTenant::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        /*
         * Only the Shopify webhook is exempt, and it is HMAC-verified by the
         * shopify.webhook middleware. The Razorpay/Stripe entries that used to
         * be listed here matched no route at all — an exemption with no
         * signature check is a hole waiting for a future route to land on it,
         * so they are gone.
         */
        $middleware->validateCsrfTokens(except: [
            'webhooks/shopify/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
