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

    'providers'     => [

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
    | prefix, path, or middleware, or disable auto-registration entirely and
    | define the route manually in your own routes/api.php.
    |
    */

    'route'         => [
        'enabled'    => true,
        'prefix'     => 'api',
        'path'       => 'social-login',
        'middleware' => ['api', 'throttle:5,1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully-qualified class name of your User model. Override this if your
    | User model lives outside the default App\Models namespace.
    |
    */

    'user_model'    => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Name
    |--------------------------------------------------------------------------
    |
    | Tell the package which column(s) on your users table hold the user's name.
    |
    | Supported strategies:
    |
    |   'single'  — Write the full name into one column.
    |               Set "column" to your column name:
    |               e.g. "name", "full_name", "display_name"
    |
    |   'split'   — Split the full name into two columns (first / last).
    |               Set "first" and "last" to your column names:
    |               e.g. first => "first_name" / last => "last_name"
    |               or   first => "f_name"     / last => "l_name"
    |
    */

    'name_field'    => [
        'strategy' => 'single',     // 'single' | 'split'
        'column'   => 'name',       // used when strategy = 'single'
        'first'    => 'first_name', // used when strategy = 'split'
        'last'     => 'last_name',  // used when strategy = 'split'
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Avatar
    |--------------------------------------------------------------------------
    |
    | Tell the package which column stores the avatar path, and where on disk
    | the downloaded image should be saved.
    |
    | Set "column" to match your users table:
    |   e.g. "avatar", "avatar_path", "image", "profile_image", "user_image"
    |
    | Set "disk" to any Laravel filesystem disk defined in config/filesystems.php.
    | Use "local_public" (default) to store under the public/ directory via
    | public_path(), which does not require Laravel Storage configuration.
    |
    | "folder" is the subdirectory within the disk root where files are saved.
    |
    | Set "enabled" to false to skip avatar download entirely.
    |
    */

    'avatar'        => [
        'enabled' => true,
        'column'  => 'avatar_path',  // e.g. 'avatar', 'image', 'profile_image', 'user_image'
        'disk'    => 'local_public', // 'local_public' | any Laravel disk key
        'folder'  => env('PROFILE_IMAGE_FOLDER', 'uploads/profileImages'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Username
    |--------------------------------------------------------------------------
    |
    | Set "enabled" to true if your users table has a username column.
    | Set "column" to match your actual column name.
    |
    | The package will auto-generate a unique URL-safe username derived from
    | the user's display name, appending a numeric suffix when needed.
    |
    | Set "enabled" to false to skip username handling entirely — no column
    | will be written and no uniqueness check will be performed.
    |
    */

    'username'      => [
        'enabled' => true,
        'column'  => 'username', // e.g. 'username', 'user_name', 'handle'
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Active Status
    |--------------------------------------------------------------------------
    |
    | Set "enabled" to true if your users table has an active status column.
    | "value" is what gets written when a user is created or logs in.
    |
    */

    'active_status' => [
        'enabled' => true,
        'column'  => 'is_active', // e.g. 'is_active', 'status', 'active'
        'value'   => true,
    ],
    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback values used when the social provider does not return a value.
    |
    */

    'defaults'      => [
        'name' => 'Unknown User',
    ],

];
