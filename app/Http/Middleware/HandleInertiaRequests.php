<?php

namespace App\Http\Middleware;

use App\Services\AccessScope;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing('profile');
        $scope = app(AccessScope::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => fn () => $user ? $scope->assignments($user, [...AccessScope::COORDINATORS, ...AccessScope::KEEPERS, 'Pegawai'])->with('role')->get()->pluck('role.name')->unique()->values() : [],
                'can' => [
                    'coordinate' => $user?->hasRole(AccessScope::COORDINATORS) ?? false,
                    'inspect' => $user?->hasRole(AccessScope::KEEPERS) ?? false,
                    'administer' => $user ? $scope->administer($user) : false,
                ],
            ],
            'unreadNotifications' => fn () => $user?->unreadNotifications()->count() ?? 0,
            'flash' => ['success' => fn () => $request->session()->get('success'), 'error' => fn () => $request->session()->get('error'), 'status' => fn () => $request->session()->get('status')],
        ];
    }
}
