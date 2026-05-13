<?php

use App\Providers\RouteServiceProvider;
use Laravel\Fortify\Features;

return [
    'guard' => 'web',

    'middleware' => ['web'],

    'auth_middleware' => 'auth',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => false,

    'home' => '/attendance',

    'prefix' => '',

    'domain' => null,

    'views' => true,

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'null',
    ],

    'login_view' => null,

    'register_view' => null,

    'reset_password_view' => null,

    'email_verification_view' => null,

    'confirm_password_view' => null,
];