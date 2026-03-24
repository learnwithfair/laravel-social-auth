<?php
namespace Learnwithfair\SocialAuth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Learnwithfair\SocialAuth\Traits\ApiResponse;
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

        $provider = $validated['provider'];
        $token    = $validated['provider_id'];
        $device   = $validated['device'];

        try {
            $driver = $this->resolveDriver($provider, $device);

            $socialiteUser = $driver->userFromToken($token);

            if (! $socialiteUser || ! $socialiteUser->getEmail()) {
                return $this->error(null, 'Invalid social token or missing email.', 422);
            }

            $user         = $this->findOrProvisionUser($socialiteUser, $provider);
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

        if (! $config) {
            throw new \InvalidArgumentException("No configuration found for provider [{$provider}] on device [{$device}].");
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
     * Find an existing user by email or create a new one.
     */
    protected function findOrProvisionUser(mixed $socialiteUser, string $provider): User
    {
        $email      = $socialiteUser->getEmail();
        $name       = $socialiteUser->getName() ?? config('social-auth.defaults.name', 'Unknown User');
        $avatarUrl  = $socialiteUser->getAvatar();
        $providerId = $socialiteUser->getId();

        $user = User::firstOrNew(['email' => $email]);

        $avatarPath = $this->downloadAvatar($avatarUrl, $user->exists ? $user->avatar_path : null);

        $freePlanId = $this->resolvePlanId();

        $user->fill([
            'name'              => $name,
            'username'          => $user->exists ? $user->username : $this->generateUniqueUsername($name),
            'password'          => $user->exists ? $user->password : bcrypt(Str::random(32)),
            'provider'          => $provider,
            'provider_id'       => $providerId,
            'avatar_path'       => $avatarPath ?: ($user->avatar_path ?? null),
            'email_verified_at' => now(),
            'plan_id'           => $user->exists ? $user->plan_id : $freePlanId,
            'is_active'         => true,
        ])->save();

        return $user;
    }

    /**
     * Download and persist a remote avatar image.
     *
     * Returns the local relative path on success, or null on failure.
     */
    protected function downloadAvatar(?string $url, ?string $existingPath): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            if ($existingPath && function_exists('deleteFile')) {
                deleteFile($existingPath);
            }

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $folder = '/uploads/profileImages';
            $path   = public_path($folder);

            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            $filename = time() . '_' . Str::random(6) . '.jpg';
            file_put_contents($path . '/' . $filename, $response->body());

            return $folder . '/' . $filename;

        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Resolve the ID of the free plan, if the Plan model exists.
     */
    protected function resolvePlanId(): ?int
    {
        $planModel = config('social-auth.plan_model');

        if ($planModel && class_exists($planModel)) {
            return $planModel::where('slug', config('social-auth.free_plan_slug', 'free'))->value('id');
        }

        return null;
    }

    /**
     * Generate a URL-safe, unique username derived from the user's display name.
     */
    protected function generateUniqueUsername(string $name): string
    {
        $base     = Str::slug($name, '_');
        $username = $base;
        $i        = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $i++;
        }

        return $username;
    }
}
