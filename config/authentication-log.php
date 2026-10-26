<?php

return [
    'table_name' => 'authentication_log',
    'db_connection' => null,
    'events' => [
        'login' => \Illuminate\Auth\Events\Login::class,
        'failed' => \Illuminate\Auth\Events\Failed::class,
        'logout' => \Illuminate\Auth\Events\Logout::class,
        'logout-other-devices' => \Illuminate\Auth\Events\OtherDeviceLogout::class,
    ],
    'listeners' => [
        'login' => \Rappasoft\LaravelAuthenticationLog\Listeners\LoginListener::class,
        'failed' => \Rappasoft\LaravelAuthenticationLog\Listeners\FailedLoginListener::class,
        'logout' => \Rappasoft\LaravelAuthenticationLog\Listeners\LogoutListener::class,
        'logout-other-devices' => \Rappasoft\LaravelAuthenticationLog\Listeners\OtherDeviceLogoutListener::class,
    ],
    'notifications' => [
        'new-device' => [
            'enabled' => env('NEW_DEVICE_NOTIFICATION', false),
            'location' => false,
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice::class,
        ],
        'failed-login' => [
            'enabled' => env('FAILED_LOGIN_NOTIFICATION', false),
            'location' => false,
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin::class,
        ],
    ],
    'purge' => 365,
    'behind_cdn' => false,
];
