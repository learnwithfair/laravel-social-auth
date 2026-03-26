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

    'providers'       => [

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

    'route'           => [
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

    'user_model'      => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Name
    |--------------------------------------------------------------------------
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

    'name_field'      => [
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
    | Set "enabled" to false to skip avatar download entirely.
    | Set "column" to match your users table column name.
    | Set "disk" to any Laravel filesystem disk, or "local_public" to write
    |   directly under public_path() without requiring Storage configuration.
    |
    */

    'avatar'          => [
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
    | Set "enabled" to false to skip username handling entirely.
    | Set "column" to match your users table column name.
    |
    | The package auto-generates a unique URL-safe username derived from the
    | user's display name, appending a numeric suffix when needed.
    |
    */

    'username'        => [
        'enabled' => false, // enable or disable username handling
        'column'  => 'username', // e.g. 'username', 'user_name', 'handle'
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Active Status
    |--------------------------------------------------------------------------
    |
    | Set "enabled" to false to skip this field entirely.
    | Set "column" to match your users table column name.
    | Set "value" to what should be written on create and login.
    |
    */

    'active_status'   => [
        'enabled' => false, // enable or disable active status handling
        'column'  => 'is_active', // e.g. 'is_active', 'status', 'active'
        'value'   => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping — Role
    |--------------------------------------------------------------------------
    |
    | Enable this if your application supports multiple user types.
    |
    | "enabled"       — Set to true to accept and write a role value.
    |
    | "request_field" — The key the mobile client sends in the JSON payload.
    |                   e.g. if the app sends { "user_type": "instructor" }
    |                   set this to "user_type". Defaults to "role".
    |
    | "column"        — The column on your users table that stores the role.
    |                   e.g. "role", "user_type", "type", "user_role"
    |
    | "allowed"       — The values the API will accept. Any value outside this
    |                   list is rejected with a 422 validation error.
    |                   Leave empty ([]) to allow any string value.
    |
    | "default"       — The value written when the request sends no role field.
    |
    | Note: The role is only written for NEW users. Returning users always
    |       retain their existing role — the request value is ignored for them.
    |
    */

    'role'            => [
        'enabled'       => false,
        'request_field' => 'role',                 // key sent by the mobile client
        'column'        => 'role',                 // e.g. 'role', 'user_type', 'type'
        'allowed'       => ['user', 'instructor'], // leave empty [] to allow any string
        'default'       => 'user',
    ],
  

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment Protection
    |--------------------------------------------------------------------------
    |
    | Laravel models that use $fillable will silently ignore columns not listed
    | in that array. Since the package writes dynamic column names configured
    | by you, those columns are often absent from $fillable.
    |
    | 'auto'   — (recommended) Inspects the model at runtime.
    |              - If $fillable is used: package fields missing from it are
    |                temporarily added for that single save, then discarded.
    |                No changes to your User model are required.
    |              - If $guarded is used: writes directly.
    |
    | 'bypass' — Always uses forceFill(). Bypasses all protection.
    |
    | 'strict' — Uses fill() only. You must add every package column to your
    |            model's $fillable array manually.
    |
    */

    'mass_assignment' => 'auto', // 'auto' | 'bypass' | 'strict'

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback values used when the social provider does not return a value.
    |
    */

    'defaults'        => [
        'name' => 'Unknown User',
    ],

];
