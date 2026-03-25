<?php
namespace RahatulRabbi\SocialAuth;

use Illuminate\Support\ServiceProvider;
use RahatulRabbi\SocialAuth\Commands\InstallCommand;
use RahatulRabbi\SocialAuth\Commands\UninstallCommand;

class SocialAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishables();
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/social-auth.php',
            'social-auth'
        );
    }

    /**
     * Register the Artisan commands provided by the package.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
            UninstallCommand::class,
        ]);
    }

    /**
     * Register all publishable assets.
     */
    protected function registerPublishables(): void
    {
        // Config file
        $this->publishes([
            __DIR__ . '/../config/social-auth.php' => config_path('social-auth.php'),
        ], 'social-auth-config');

        // Controller stub
        $this->publishes([
            __DIR__ . '/../stubs/SocialAuthController.stub' => app_path('Http/Controllers/Api/Auth/SocialAuthController.php'),
        ], 'social-auth-controller');

        // Migration
        $this->publishes([
            __DIR__ . '/../database/migrations/add_social_auth_columns_to_users_table.php.stub' => database_path(
                'migrations/' . date('Y_m_d_His') . '_add_social_auth_columns_to_users_table.php'
            ),
        ], 'social-auth-migrations');

        // All assets at once (used by install command)
        $this->publishes([
            __DIR__ . '/../config/social-auth.php'                                              => config_path('social-auth.php'),
            __DIR__ . '/../stubs/SocialAuthController.stub'                                     => app_path('Http/Controllers/Api/Auth/SocialAuthController.php'),
            __DIR__ . '/../database/migrations/add_social_auth_columns_to_users_table.php.stub' => database_path(
                'migrations/' . date('Y_m_d_His') . '_add_social_auth_columns_to_users_table.php'
            ),
        ], 'social-auth');
    }
}
