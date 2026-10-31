<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\CentralLogics\Helpers;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->hasHeader('X-localization') && strlen($request->header('X-localization')) > 0) {
            $local = $request->header('X-localization');
        } elseif (Session::has('local')) {
            $local = Session::get('local');
        } else {
            $local = Helpers::get_default_language();
            if ($request->hasSession()) {
                Session::put('local', $local);
                $direction = ($local == 'ar') ? 'rtl' : 'ltr';
                Session::put('direction', $direction);
            }
        }

        // set laravel localization
        App::setLocale($local);
        // continue request
        return $next($request);
    }
}
