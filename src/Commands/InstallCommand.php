<?php
namespace Learnwithfair\SocialAuth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'social-auth:install
                            {--force : Overwrite existing files without prompting}
                            {--skip-migration : Skip publishing the migration file}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel Social Auth package: publishes config, controller, and migration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('');
        $this->line('  Laravel Social Auth — Installation');
        $this->line('  -----------------------------------');
        $this->line('');

        $this->publishConfig();
        $this->publishController();

        if (! $this->option('skip-migration')) {
            $this->publishMigration();
        }

        $this->appendEnvStub();
        $this->printNextSteps();

        return self::SUCCESS;
    }

    /**
     * Publish the package configuration file.
     */
    protected function publishConfig(): void
    {
        $this->info('  Publishing configuration file...');

        $this->callSilently('vendor:publish', [
            '--tag'   => 'social-auth-config',
            '--force' => $this->option('force'),
        ]);

        $this->line('  [ok] config/social-auth.php');
    }

    /**
     * Publish the controller stub.
     */
    protected function publishController(): void
    {
        $this->info('  Publishing controller...');

        $destination = app_path('Http/Controllers/Api/Auth/SocialAuthController.php');

        if (File::exists($destination) && ! $this->option('force')) {
            if (! $this->confirm('  SocialAuthController.php already exists. Overwrite?', false)) {
                $this->line('  [skip] SocialAuthController.php');
                return;
            }
        }

        File::ensureDirectoryExists(app_path('Http/Controllers/Api/Auth'));

        $this->callSilently('vendor:publish', [
            '--tag'   => 'social-auth-controller',
            '--force' => true,
        ]);

        $this->line('  [ok] app/Http/Controllers/Api/Auth/SocialAuthController.php');
    }

    /**
     * Publish the migration stub.
     */
    protected function publishMigration(): void
    {
        $this->info('  Publishing migration...');

        $this->callSilently('vendor:publish', [
            '--tag'   => 'social-auth-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->line('  [ok] database/migrations/*_add_social_auth_columns_to_users_table.php');
    }

    /**
     * Append required .env keys if they are not already present.
     */
    protected function appendEnvStub(): void
    {
        $envPath  = base_path('.env');
        $stubPath = __DIR__ . '/../../stubs/.env.stub';

        if (! File::exists($envPath) || ! File::exists($stubPath)) {
            return;
        }

        $existing = File::get($envPath);
        $stub     = File::get($stubPath);

        if (str_contains($existing, 'CLIENT_ID_ANDROID')) {
            $this->line('  [skip] .env keys already present');
            return;
        }

        File::append($envPath, PHP_EOL . $stub);
        $this->line('  [ok] .env — Google and Apple keys appended');
    }

    /**
     * Print the post-installation checklist.
     */
    protected function printNextSteps(): void
    {
        $this->line('');
        $this->line('  Installation complete. Complete the following steps:');
        $this->line('');
        $this->line('  1. Set your credentials in .env:');
        $this->line('       CLIENT_ID_ANDROID=<your-android-client-id>');
        $this->line('       CLIENT_ID_IOS=<your-ios-client-id>');
        $this->line('');
        $this->line('  2. Run the migration:');
        $this->line('       php artisan migrate');
        $this->line('');
        $this->line('  3. Ensure Laravel Sanctum is installed and configured.');
        $this->line('');
        $this->line('  4. Register the SocialiteProviders event listener in');
        $this->line('     App\\Providers\\AppServiceProvider (see README).');
        $this->line('');
        $this->line('  5. Add the route to routes/api.php or use the package route');
        $this->line('     that is loaded automatically at POST /api/social-login.');
        $this->line('');
    }
}
