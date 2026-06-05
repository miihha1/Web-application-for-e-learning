<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsTeacher
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // teacher alebo admin môže pokračovať
        if (!$user || !($user->isTeacher() || $user->isAdmin())) {
            abort(403);
        }

        return $next($request);
    }
}
