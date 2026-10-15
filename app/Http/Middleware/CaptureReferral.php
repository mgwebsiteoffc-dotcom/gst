<?php

namespace App\Http\Middleware;

use App\Services\Partner\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures ?ref=CODE on any public page and stores it in a cookie so the
 * partner still gets credit if the visitor signs up days later.
 */
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query('ref');
        $response = $next($request);

        if (! $code || ! is_string($code) || strlen($code) > 32) {
            return $response;
        }

        // First touch wins — don't let a later partner steal the attribution.
        if ($request->cookie(ReferralService::COOKIE)) {
            return $response;
        }

        $minutes = app(ReferralService::class)->track($request, $code);

        if ($minutes) {
            $response->headers->setCookie(
                cookie(ReferralService::COOKIE, strtoupper($code), $minutes, null, null, null, false)
            );
            $response->headers->setCookie(
                cookie('bb_visitor', $request->cookie('bb_visitor') ?: (string) Str::uuid(), $minutes)
            );
        }

        return $response;
    }
}
