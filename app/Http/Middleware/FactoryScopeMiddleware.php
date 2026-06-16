<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FactoryScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            session(['current_factory_id' => $user->factory_id]);
            session(['is_parent_factory' => $user->is_parent_factory]);
        }

        return $next($request);
    }
}
