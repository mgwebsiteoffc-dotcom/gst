<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::ALLOWED, true), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
