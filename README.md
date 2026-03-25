# Laravel Social Auth

One-command Google and Apple social login for Laravel mobile APIs.

Supports Android and iOS with separate client IDs per platform, stateless token verification, automatic user provisioning, and Laravel Sanctum token issuance. All column names are fully configurable — no assumptions are made about your users table structure.

---

## Requirements

- PHP 8.1 or higher
- Laravel 10 or 11
- Laravel Sanctum 3 or 4
- Laravel Socialite 5

---

## Installation

**Step 1 — Install the package via Composer:**

```bash
composer require rahatulrabbi/laravel-social-auth
```

**Step 2 — Run the install command:**

```bash
php artisan social-auth:install
```

This single command:

- Publishes `config/social-auth.php`
- Publishes `app/Http/Controllers/Api/Auth/SocialAuthController.php`
- Publishes a migration that reads your config at runtime to create the correct columns
- Appends the necessary keys to your `.env` file

**Step 3 — Set your credentials in `.env`:**

```env
CLIENT_ID_ANDROID=your-android-client-id.apps.googleusercontent.com
CLIENT_ID_IOS=your-ios-client-id.apps.googleusercontent.com
CLIENT_SECRET=
REDIRECT_URI=
```

**Step 4 — Configure field mappings in `config/social-auth.php`:**

Open the published config and adjust every section to match your actual `users` table column names. Do this **before** running the migration — the migration reads this config at runtime to know which columns to create.

```php
// Name strategy: write to one column, or split into first/last
'name_field' => [
    'strategy' => 'single',   // 'single' | 'split'
    'column'   => 'name',     // used when strategy = single
    'first'    => 'first_name', // used when strategy = split
    'last'     => 'last_name',  // used when strategy = split
],

// Avatar column name and storage disk
'avatar' => [
    'enabled' => true,
    'column'  => 'avatar_path', // e.g. 'avatar', 'image', 'profile_image', 'user_image'
    'disk'    => 'local_public', // 'local_public' | any Laravel disk key
    'folder'  => env('PROFILE_IMAGE_FOLDER', 'uploads/profileImages'),
],

// Username generation — disable entirely if your table has no username column
'username' => [
    'enabled' => true,
    'column'  => 'username', // e.g. 'username', 'user_name', 'handle'
],

// Active status column
'active_status' => [
    'enabled' => true,
    'column'  => 'is_active', // e.g. 'is_active', 'status', 'active'
    'value'   => true,
],
```

**Step 5 — Run the migration:**

```bash
php artisan migrate
```

The migration reads your published `config/social-auth.php` and creates only the columns you have enabled, using exactly the column names you configured.

**Step 6 — Register the SocialiteProviders event listener (Laravel 10 only).**

Laravel 11 and above: this step is not required. SocialiteProviders v5+ registers itself automatically.

Laravel 10 only — open `app/Providers/AppServiceProvider.php` and add the following inside the `boot` method:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(function (SocialiteWasCalled $event) {
        $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
        $event->extendSocialite('apple',  \SocialiteProviders\Apple\Provider::class);
    });
}
```

---

## Route

The package automatically registers the following route:

```
POST /api/social-login
```

Rate limited to 5 requests per minute by default. The route prefix, path, middleware, and enabled state are all configurable in `config/social-auth.php` under the `route` key.

---

## Request Payload

| Field       | Type   | Required | Values        | Description                      |
|-------------|--------|----------|---------------|----------------------------------|
| provider    | string | Yes      | google, apple | The social provider              |
| provider_id | string | Yes      | —             | The ID token from the mobile SDK |
| device      | string | Yes      | android, ios  | The device type                  |

**Example — Google Android:**

```json
{
  "provider": "google",
  "provider_id": "ya29.a0AfH6SMDxxxxxxxx",
  "device": "android"
}
```

**Example — Apple iOS:**

```json
{
  "provider": "apple",
  "provider_id": "eyJraWQiOiJBMkQz...",
  "device": "ios"
}
```

---

## Response

**Success (200):**

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

**New user registered (200):**

```json
{
  "success": true,
  "message": "User registered successfully.",
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

**Invalid token (422):**

```json
{
  "success": false,
  "message": "Invalid social token or missing email.",
  "data": null
}
```

**Server error (500):**

```json
{
  "success": false,
  "message": "Social login failed.",
  "data": {
    "error": "Detailed error message here."
  }
}
```

---

## Field Mapping Reference

All field mappings are controlled from `config/social-auth.php`. The migration and the controller both read this config at runtime, so the two are always in sync.

### Name Strategy

| Strategy | Config keys used          | Example columns               |
|----------|---------------------------|-------------------------------|
| single   | `name_field.column`       | name, full_name, display_name |
| split    | `name_field.first` + `last` | first_name / last_name, f_name / l_name |

### Avatar Storage Disk

| Disk value    | Where files are saved                              |
|---------------|----------------------------------------------------|
| local_public  | `public_path(folder)` — no Storage config needed  |
| public        | `storage/app/public/` via Laravel Storage          |
| s3            | AWS S3 via Laravel Storage                         |
| any disk key  | Any disk defined in `config/filesystems.php`       |

### Quick Reference

| Feature       | Config key                  | Disable by setting         |
|---------------|-----------------------------|----------------------------|
| Username      | `username.enabled`          | `false`                    |
| Avatar        | `avatar.enabled`            | `false`                    |
| Active status | `active_status.enabled`     | `false`                    |
| Plan          | `plan.model`                | `null`                     |

---

## Customisation

The published `SocialAuthController` in your application extends the package base controller. Override any method to customise behaviour without losing future package updates.

```php
namespace App\Http\Controllers\Api\Auth;

use RahatulRabbi\SocialAuth\Http\Controllers\SocialAuthController as BaseSocialAuthController;

class SocialAuthController extends BaseSocialAuthController
{
    // Example: assign a referral code to new users
    protected function findOrProvisionUser(mixed $socialiteUser, string $provider): mixed
    {
        $user = parent::findOrProvisionUser($socialiteUser, $provider);

        if ($user->wasRecentlyCreated) {
            $user->referral_code = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
            $user->save();
        }

        return $user;
    }
}
```

**Available override points:**

| Method                     | Purpose                                               |
|----------------------------|-------------------------------------------------------|
| `resolveDriver`            | Change how the Socialite driver is built              |
| `findOrProvisionUser`      | Customise user creation and update logic              |
| `resolveNameFields`        | Override name splitting beyond what config supports   |
| `resolvePlanFields`        | Customise plan assignment for new users               |
| `downloadAvatar`           | Replace the avatar download and storage strategy      |
| `generateUniqueUsername`   | Change the username generation algorithm              |

---

## Disabling the Auto-Registered Route

Set `route.enabled` to `false` in `config/social-auth.php`, then define the route manually:

```php
// routes/api.php
use App\Http\Controllers\Api\Auth\SocialAuthController;

Route::post('/social-login', [SocialAuthController::class, 'socialLogin'])
    ->middleware('throttle:5,1');
```

---

## Publishing Assets Individually

```bash
# Config only
php artisan vendor:publish --tag=social-auth-config

# Controller only
php artisan vendor:publish --tag=social-auth-controller

# Migration only
php artisan vendor:publish --tag=social-auth-migrations
```

---

## Common Errors

**`Invalid ID token audience`**

The token was verified against the wrong client ID. Confirm that Android tokens are sent with `device=android` and iOS tokens with `device=ios`. Check that `CLIENT_ID_ANDROID` and `CLIENT_ID_IOS` in your `.env` match the credentials registered in Google Cloud Console.

**`No configuration found for provider [google] on device [android]`**

Your `CLIENT_ID_ANDROID` or `CLIENT_ID_IOS` in `.env` is empty. Fill in both values and run `php artisan config:clear`.

**`Undefined method stateless()`**

The SocialiteProviders event listener is not registered. Follow Step 6 above (Laravel 10 only).

**`Class "SocialiteProviders\Apple\Provider" not found`**

Run `composer require socialiteproviders/apple`. This is declared as a dependency and should be installed automatically, but may be missing if Composer constraints prevented it.

**Migration created wrong column names**

You ran `php artisan migrate` before configuring `config/social-auth.php`. Roll back with `php artisan migrate:rollback`, update your config, then migrate again.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## License

MIT. See [LICENSE](LICENSE).

---

## Author

Built by [MD. Rahatul Rabbi](https://github.com/learnwithfair).