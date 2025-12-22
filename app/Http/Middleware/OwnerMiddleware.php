<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Jika user bukan owner, redirect ke halaman kasir dashboard
        if ($user->role !== 'owner') {
            // Tentukan halaman redirect berdasarkan role
            if ($user->role === 'kasir') {
                return redirect()->route('transaksi.create')->with('error', 'Akses ditolak. Hanya owner yang dapat mengakses halaman ini.');
            }
            
            // Default redirect ke home
            return redirect('/')->with('error', 'Akses ditolak. Hanya owner yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}