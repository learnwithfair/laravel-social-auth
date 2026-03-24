<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social Auth Providers
    |--------------------------------------------------------------------------
    |
    | Configure client IDs and secrets for Google and Apple per device type.
    | Each provider supports "android" and "ios" keys so that the correct
    | OAuth audience is resolved at runtime.
    |
    */

    'providers'      => [

        'google' => [
            'android' => [
                'client_id'     => env('CLIENT_ID_ANDROID'),
                'client_secret' => env('CLIENT_SECRET', ''),
                'redirect'      => env('REDIRECT_URI', ''),
            ],
            'ios'     => [
                'client_id'     => env('CLIENT_ID_IOS'),
                'client_secret' => env('CLIENT_SECRET', ''),
                'redirect'      => env('REDIRECT_URI', ''),
            ],
        ],

        'apple'  => [
            'android' => [
                'client_id'     => env('CLIENT_ID_ANDROID'),
                'client_secret' => env('CLIENT_SECRET', ''),
                'redirect'      => env('REDIRECT_URI', ''),
            ],
            'ios'     => [
                'client_id'     => env('CLIENT_ID_IOS'),
                'client_secret' => env('CLIENT_SECRET', ''),
                'redirect'      => env('REDIRECT_URI', ''),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | The package registers a POST route automatically. You may change the
    | prefix or disable auto-registration and define the route manually.
    |
    */

    'route'          => [
        'enabled'    => true,
        'prefix'     => 'api',
        'path'       => 'social-login',
        'middleware' => ['api', 'throttle:5,1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Integration
    |--------------------------------------------------------------------------
    |
    | If your application uses a Plan model, set the fully-qualified class name
    | here. New users will be assigned the plan identified by "free_plan_slug".
    | Set "plan_model" to null to disable plan assignment entirely.
    |
    */

    'plan_model'     => null, // e.g. App\Models\Plan
    'free_plan_slug' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'defaults'       => [
        'name' => 'Unknown User',
    ],

];
