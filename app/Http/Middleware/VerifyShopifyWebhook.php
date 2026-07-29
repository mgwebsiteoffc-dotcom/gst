<?php

namespace App\Http\Middleware;

use App\Services\Shopify\ShopifyOAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! ShopifyOAuthService::verifyWebhook($request->getContent(), $request->header('X-Shopify-Hmac-Sha256'))) {
            abort(401, 'Invalid Shopify webhook signature.');
        }

        return $next($request);
    }
}
