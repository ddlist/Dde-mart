<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * DDE-Mart Admin — panel locale from session (original middleware).
 * Only locales with a `lang/{locale}` directory apply; everything else
 * falls back to English. Full translation files arrive with panel i18n.
 */
class PanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('panel_locale', config('app.locale'));

        if (is_dir(lang_path($locale))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
