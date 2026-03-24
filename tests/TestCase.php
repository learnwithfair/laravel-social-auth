<?php
namespace Learnwithfair\SocialAuth\Tests;

use Learnwithfair\SocialAuth\SocialAuthServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Register the package service provider.
     */
    protected function getPackageProviders($app): array
    {
        return [
            SocialAuthServiceProvider::class,
        ];
    }

    /**
     * Define the environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('social-auth.providers.google.android.client_id', 'test-android-client-id');
        $app['config']->set('social-auth.providers.google.ios.client_id', 'test-ios-client-id');
        $app['config']->set('social-auth.route.enabled', true);
    }
}
