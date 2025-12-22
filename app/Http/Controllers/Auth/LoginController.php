<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // OVERRIDE METHOD authenticated UNTUK REDIRECT BERDASARKAN ROLE
    protected function authenticated(Request $request, $user)
    {
        if ($user->role === 'owner') {
            return redirect()->route('owner.dashboard');
        } elseif ($user->role === 'kasir') {
            return redirect()->route('kasir.dashboard');
        }

        return redirect('/dashboard');
    }

    // OVERRIDE METHOD loggedOut
    protected function loggedOut(Request $request)
    {
        return redirect('/login');
    }
}