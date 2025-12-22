<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  ...$guards
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirect ke dashboard berdasarkan role
                $user = Auth::guard($guard)->user();
                
                if ($user->role === 'owner') {
                    return redirect()->route('owner.dashboard');
                } elseif ($user->role === 'kasir') {
                    return redirect()->route('kasir.dashboard');
                }
                
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}