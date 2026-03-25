<?php

use Illuminate\Support\Facades\Route;
use RahatulRabbi\SocialAuth\Http\Controllers\SocialAuthController;

if (config('social-auth.route.enabled', true)) {
    Route::prefix(config('social-auth.route.prefix', 'api'))
        ->middleware(config('social-auth.route.middleware', ['api', 'throttle:5,1']))
        ->group(function () {
            Route::post(
                config('social-auth.route.path', 'social-login'),
                [SocialAuthController::class, 'socialLogin']
            )->name('social-auth.login');
        });
}
