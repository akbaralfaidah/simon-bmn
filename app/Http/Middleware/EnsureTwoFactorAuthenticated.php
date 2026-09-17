<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AccessScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public static function required(User $user): bool
    {
        return $user->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]);
    }

    public static function verified(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->hasEnabledTwoFactorAuthentication()
            && $request->session()->get('mfa.user_id') === $user->id
            && hash_equals(hash('sha256', $user->two_factor_secret), (string) $request->session()->get('mfa.secret_hash'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (self::required($user) || $user->hasEnabledTwoFactorAuthentication()) && ! self::verified($request)) {
            return to_route('two-factor.show');
        }

        return $next($request);
    }
}
