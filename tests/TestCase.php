<?php
namespace RahatulRabbi\SocialAuth\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RahatulRabbi\SocialAuth\SocialAuthServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Register the package service provider.
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Laravel\Sanctum\SanctumServiceProvider::class,
            \Laravel\Socialite\SocialiteServiceProvider::class,
            SocialAuthServiceProvider::class,
        ];
    }

    /**
     * Define the environment setup.
     *
     * Runs before every test. Sets a complete, realistic config so individual
     * tests only need to override the keys relevant to what they are testing.
     */
    protected function defineEnvironment($app): void
    {
        // Database — use SQLite in-memory for speed
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Sanctum
        $app['config']->set('auth.guards.sanctum', [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ]);

        // Provider credentials
        $app['config']->set('social-auth.providers.google.android.client_id', 'test-android-client-id');
        $app['config']->set('social-auth.providers.google.android.client_secret', '');
        $app['config']->set('social-auth.providers.google.android.redirect', '');
        $app['config']->set('social-auth.providers.google.ios.client_id', 'test-ios-client-id');
        $app['config']->set('social-auth.providers.google.ios.client_secret', '');
        $app['config']->set('social-auth.providers.google.ios.redirect', '');
        $app['config']->set('social-auth.providers.apple.android.client_id', 'test-android-client-id');
        $app['config']->set('social-auth.providers.apple.ios.client_id', 'test-ios-client-id');

        // Route
        $app['config']->set('social-auth.route.enabled', true);
        $app['config']->set('social-auth.route.prefix', 'api');
        $app['config']->set('social-auth.route.path', 'social-login');
        $app['config']->set('social-auth.route.middleware', ['api']);

        // User model
        $app['config']->set('social-auth.user_model', \App\Models\User::class);

        // Name field — default single strategy
        $app['config']->set('social-auth.name_field.strategy', 'single');
        $app['config']->set('social-auth.name_field.column', 'name');
        $app['config']->set('social-auth.name_field.first', 'first_name');
        $app['config']->set('social-auth.name_field.last', 'last_name');

        // Avatar
        $app['config']->set('social-auth.avatar.enabled', true);
        $app['config']->set('social-auth.avatar.column', 'avatar_path');
        $app['config']->set('social-auth.avatar.disk', 'local_public');
        $app['config']->set('social-auth.avatar.folder', 'uploads/profileImages');

        // Username
        $app['config']->set('social-auth.username.enabled', true);
        $app['config']->set('social-auth.username.column', 'username');

        // Active status
        $app['config']->set('social-auth.active_status.enabled', true);
        $app['config']->set('social-auth.active_status.column', 'is_active');
        $app['config']->set('social-auth.active_status.value', true);

        // Plan — disabled by default in tests
        $app['config']->set('social-auth.plan.model', null);
        $app['config']->set('social-auth.plan.slug', 'free');

        // Defaults
        $app['config']->set('social-auth.defaults.name', 'Unknown User');
    }

    /**
     * Define the database migrations that should run before each test.
     *
     * Testbench will run these automatically when RefreshDatabase is used.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
