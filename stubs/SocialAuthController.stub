<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use RahatulRabbi\SocialAuth\Http\Controllers\SocialAuthController as BaseSocialAuthController;

/**
 * SocialAuthController
 *
 * Extends the package base controller. Override any method here to customise
 * behaviour for your application without losing future package updates.
 *
 * Available override points:
 *
 *   resolveDriver(string $provider, string $device)
 *       Change how the Socialite driver is built.
 *
 *   findOrProvisionUser(mixed $socialiteUser, string $provider)
 *       Customise user creation and update logic, e.g. add extra fields.
 *
 *   resolveNameFields(string $fullName)
 *       Override name splitting logic beyond what config supports.
 *
 *   downloadAvatar(?string $url, ?string $existingPath)
 *       Replace the avatar download and storage strategy entirely.
 *
 *   generateUniqueUsername(string $name, string $userModel, string $column)
 *       Change the username generation algorithm.
 *
 * All field mapping (name strategy, avatar column, username column, active
 * status column) is configured in config/social-auth.php —
 * you should only need to override here for logic that config cannot express.
 *
 * Example — add a referral code for new users:
 *
 *   protected function findOrProvisionUser(mixed $socialiteUser, string $provider): mixed
 *   {
 *       $user = parent::findOrProvisionUser($socialiteUser, $provider);
 *
 *       if ($user->wasRecentlyCreated) {
 *           $user->referral_code = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
 *           $user->save();
 *       }
 *
 *       return $user;
 *   }
 */
class SocialAuthController extends BaseSocialAuthController
{
    //
}
