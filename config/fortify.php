<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'username' => 'email',
    'email' => 'email',
    'home' => '/dashboard',
    'views' => false,
    'features' => [
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true, 'secret-length' => 32, 'window' => 1]),
    ],
];
