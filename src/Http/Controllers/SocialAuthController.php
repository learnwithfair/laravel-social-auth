<?php
namespace RahatulRabbi\SocialAuth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use RahatulRabbi\SocialAuth\Traits\ApiResponse;
use SocialiteProviders\Apple\Provider as AppleProvider;

class SocialAuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle a social login request from a mobile client.
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|string',
            'provider'    => 'required|in:google,apple',
            'device'      => 'required|in:android,ios',
        ]);

        try {
            $driver        = $this->resolveDriver($validated['provider'], $validated['device']);
            $socialiteUser = $driver->userFromToken($validated['provider_id']);

            if (! $socialiteUser || ! $socialiteUser->getEmail()) {
                return $this->error(null, 'Invalid social token or missing email.', 422);
            }

            $user         = $this->findOrProvisionUser($socialiteUser, $validated['provider']);
            $sanctumToken = $user->createToken('mobile')->plainTextToken;

            return $this->success([
                'user'       => $user,
                'token'      => $sanctumToken,
                'token_type' => 'Bearer',
            ], $user->wasRecentlyCreated ? 'User registered successfully.' : 'Login successful.');

        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 'Social login failed.', 500);
        }
    }

    /**
     * Resolve the correct Socialite driver for the given provider and device.
     */
    protected function resolveDriver(string $provider, string $device): mixed
    {
        $config = config("social-auth.providers.{$provider}.{$device}");

        if (! $config || empty($config['client_id'])) {
            throw new \InvalidArgumentException(
                "No configuration found for provider [{$provider}] on device [{$device}]. " .
                "Check your social-auth.php config and .env credentials."
            );
        }

        $providerClass = match ($provider) {
            'google' => GoogleProvider::class,
            'apple'  => AppleProvider::class,
            default  => throw new \InvalidArgumentException("Unsupported provider [{$provider}]."),
        };

        return Socialite::buildProvider($providerClass, [
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'] ?? '',
            'redirect'      => $config['redirect'] ?? '',
        ])->stateless();
    }

    /**
     * Find an existing user by email or create a new one, applying all
     * configured field mappings from config/social-auth.php.
     */
    protected function findOrProvisionUser(mixed $socialiteUser, string $provider): mixed
    {
        $userModel = config('social-auth.user_model', \App\Models\User::class);

        $email      = $socialiteUser->getEmail();
        $fullName   = $socialiteUser->getName() ?? config('social-auth.defaults.name', 'Unknown User');
        $avatarUrl  = $socialiteUser->getAvatar();
        $providerId = $socialiteUser->getId();

        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user   = $userModel::firstOrNew(['email' => $email]);
        $fields = [];

        // Name field mapping
        $fields = array_merge($fields, $this->resolveNameFields($fullName));

        // Username field mapping
        if (config('social-auth.username.enabled', true)) {
            $usernameColumn          = config('social-auth.username.column', 'username');
            $fields[$usernameColumn] = $user->exists ? $user->{$usernameColumn} : $this->generateUniqueUsername($fullName, $userModel, $usernameColumn);
        }

        $fields['password']          = $user->exists ? $user->password : bcrypt(Str::random(32));
        $fields['provider']          = $provider;
        $fields['provider_id']       = $providerId;
        $fields['email_verified_at'] = now();

        //  Active status field mapping
        if (config('social-auth.active_status.enabled', true)) {
            $statusColumn          = config('social-auth.active_status.column', 'is_active');
            $fields[$statusColumn] = config('social-auth.active_status.value', true);
        }

        //  Avatar field mapping
        if (config('social-auth.avatar.enabled', true)) {
            $avatarColumn          = config('social-auth.avatar.column', 'avatar_path');
            $existingAvatar        = $user->exists ? ($user->{$avatarColumn} ?? null) : null;
            $downloadedPath        = $this->downloadAvatar($avatarUrl, $existingAvatar);
            $fields[$avatarColumn] = $downloadedPath ?: $existingAvatar;
        }

        $user->fill($fields)->save();

        return $user;
    }

    /**
     * Resolve name fields based on the configured strategy.
     *
     * 'single' — writes full name to one column (e.g. name, full_name, display_name)
     * 'split'  — splits full name across two columns (e.g. first_name / last_name)
     */
    protected function resolveNameFields(string $fullName): array
    {
        $strategy = config('social-auth.name_field.strategy', 'single');

        if ($strategy === 'split') {
            $parts     = explode(' ', trim($fullName), 2);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? '';

            return [
                config('social-auth.name_field.first', 'first_name') => $firstName,
                config('social-auth.name_field.last', 'last_name')   => $lastName,
            ];
        }

        // Default: single column
        return [config('social-auth.name_field.column', 'name') => $fullName];
    }

    /**
     * Download a remote avatar image and persist it to the configured disk.
     *
     * Returns the stored path on success, or null on any failure.
     */
    protected function downloadAvatar(?string $url, ?string $existingPath): ?string
    {
        if (! $url) {return null;}

        try {
            $response = Http::timeout(10)->get($url);
            if (! $response->successful()) {return null;}

            $disk   = config('social-auth.avatar.disk', 'local_public');
            $folder = trim(config('social-auth.avatar.folder', 'uploads/profileImages'), '/');

            // local_public: write directly to public_path() — no Storage disk needed
            if ($disk === 'local_public') {
                $this->deleteLocalAvatar($existingPath);

                $directory = public_path($folder);
                if (! is_dir($directory)) {mkdir($directory, 0755, true);}

                $filename = time() . '_' . Str::random(8) . '.jpg';
                file_put_contents($directory . '/' . $filename, $response->body());

                return $folder . '/' . $filename;
            }

            // Any configured Laravel Storage disk (public, s3, etc.)
            $this->deleteStorageAvatar($existingPath, $disk);
            $filename = $folder . '/' . time() . '_' . Str::random(8) . '.jpg';
            Storage::disk($disk)->put($filename, $response->body());

            return $filename;

        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Delete a previously stored avatar from the public directory.
     */
    protected function deleteLocalAvatar(?string $path): void
    {
        if (! $path) {return;}

        $fullPath = public_path(ltrim($path, '/'));
        if (file_exists($fullPath)) {@unlink($fullPath);}
    }

    /**
     * Delete a previously stored avatar from a Laravel Storage disk.
     */
    protected function deleteStorageAvatar(?string $path, string $disk): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * Generate a URL-safe, unique username derived from the user's display name.
     * Appends an incrementing numeric suffix until uniqueness is confirmed.
     */
    protected function generateUniqueUsername(string $name, string $userModel, string $column): string
    {
        $base     = Str::slug($name, '_') ?: 'user';
        $username = $base;
        $i        = 1;

        while ($userModel::where($column, $username)->exists()) {
            $username = $base . '_' . $i++;
        }

        return $username;
    }
}
