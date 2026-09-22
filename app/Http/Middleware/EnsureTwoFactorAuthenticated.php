<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public static function required(User $user): bool
    {
        return false;
    }

    public static function verified(Request $request): bool
    {
        return true;
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
