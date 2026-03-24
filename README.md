# Laravel Social Auth

One-command Google and Apple social login for Laravel mobile APIs.

Supports Android and iOS with separate client IDs per platform, stateless token verification, automatic user provisioning, and Laravel Sanctum token issuance.

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
composer require learnwithfair/laravel-social-auth
```

**Step 2 — Run the install command:**

```bash
php artisan social-auth:install
```

This single command:

- Publishes `config/social-auth.php`
- Publishes `app/Http/Controllers/Api/Auth/SocialAuthController.php`
- Publishes a migration to add the required columns to your `users` table
- Appends the necessary keys to your `.env` file

**Step 3 — Set your credentials in `.env`:**

```env
CLIENT_ID_ANDROID=587819760808-xxxx.apps.googleusercontent.com
CLIENT_ID_IOS=587819760808-yyyy.apps.googleusercontent.com
CLIENT_SECRET=
REDIRECT_URI=
```

**Step 4 — Run the migration:**

```bash
php artisan migrate
```

**Step 5 — Register the SocialiteProviders event listener.**

Open `app/Providers/AppServiceProvider.php` and add the following inside the `boot` method:

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

Rate limited to 5 requests per minute by default. You can change the route path, prefix, middleware, and disable auto-registration in `config/social-auth.php`.

---

## Request Payload

| Field       | Type   | Required | Values           | Description                        |
|-------------|--------|----------|------------------|------------------------------------|
| provider    | string | Yes      | google, apple    | The social provider                |
| provider_id | string | Yes      | —                | The ID token from the mobile SDK   |
| device      | string | Yes      | android, ios     | The device type                    |

**Example:**

```json
{
  "provider": "google",
  "provider_id": "ya29.a0AfH6SMDxxxxxxxx",
  "device": "android"
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

**Error (422 — invalid token):**

```json
{
  "success": false,
  "message": "Invalid social token or missing email.",
  "data": null
}
```

---

## Configuration

After publishing, `config/social-auth.php` provides full control:

```php
// Disable the auto-registered route and define it manually
'route' => [
    'enabled'    => false,
    ...
],

// Connect your Plan model for automatic free-plan assignment
'plan_model'     => \App\Models\Plan::class,
'free_plan_slug' => 'free',
```

---

## Customisation

The published `SocialAuthController` extends the package base controller. Override any method to customise behaviour without losing future package updates:

```php
protected function findOrProvisionUser(mixed $socialiteUser, string $provider): User
{
    $user = parent::findOrProvisionUser($socialiteUser, $provider);

    // Add extra logic here, e.g. assign a referral code
    $user->referral_code = Str::upper(Str::random(8));
    $user->save();

    return $user;
}
```

Available override points:

- `findOrProvisionUser` — user creation and update logic
- `downloadAvatar` — avatar download and storage
- `resolvePlanId` — plan assignment
- `generateUniqueUsername` — username generation strategy

---

## Disabling the Package Route

If you prefer to define the route yourself, set `route.enabled` to `false` in `config/social-auth.php`, then add the route manually in `routes/api.php`:

```php
use App\Http\Controllers\Api\Auth\SocialAuthController;

Route::post('/social-login', [SocialAuthController::class, 'socialLogin'])
    ->middleware('throttle:5,1');
```

---

## Publish Options

Publish assets individually using tags:

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

The token was issued for a different client ID. Verify that Android tokens are sent with `device=android` and iOS tokens are sent with `device=ios`.

**`Undefined method stateless()`**

Ensure the SocialiteProviders event listener is registered as described in Step 5 above.

**`Class "SocialiteProviders\Apple\Provider" not found`**

Run `composer require socialiteproviders/apple` if you are using Apple login. The package declares it as a dependency, so this should be resolved automatically by Composer.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## License

MIT. See [LICENSE](LICENSE).

---

## Author

Built by [MD. Rahatul Rabbi](https://github.com/learnwithfair).