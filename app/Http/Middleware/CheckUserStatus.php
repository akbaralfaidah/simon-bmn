<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            if (auth()->user()->status === 'pending') {
                return redirect()->route('pending.notice');
            }
            if (auth()->user()->status === 'suspended') {
                auth()->logout();
                return redirect()->route('login')->with('status', 'Akun Anda telah ditangguhkan.');
            }
        }
        return $next($request);
    }
}
