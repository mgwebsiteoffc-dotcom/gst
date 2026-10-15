<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor language: English, Hindi or Hinglish, remembered in the session.
 * Switch via GET /locale/{locale} (see LocaleController).
 */
class SetLocale
{
    public const ALLOWED = ['en', 'hi', 'hinglish'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! in_array($locale, self::ALLOWED, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
