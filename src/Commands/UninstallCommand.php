<?php

namespace RahatulRabbi\SocialAuth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'social-auth:uninstall
                            {--force : Skip all confirmation prompts and remove everything}
                            {--skip-migration : Do not roll back the database migration}
                            {--skip-env : Do not remove .env keys}';

    /**
     * The console command description.
     */
    protected $description = 'Remove all files and database columns published by the Social Auth package';

    /**
     * Files published by the package, relative to the Laravel root.
     */
    protected array $publishedFiles = [
        'config/social-auth.php',
        'app/Http/Controllers/Api/Auth/SocialAuthController.php',
    ];

    /**
     * .env keys injected by the package.
     */
    protected array $envKeys = [
        'CLIENT_ID_ANDROID',
        'CLIENT_ID_IOS',
        'CLIENT_SECRET',
        'REDIRECT_URI',
        'PROFILE_IMAGE_FOLDER',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('');
        $this->line('  Laravel Social Auth — Uninstall');
        $this->line('  --------------------------------');
        $this->line('');

        if (! $this->option('force')) {
            $this->warn('  This command will:');
            $this->line('    - Delete published config and controller files');
            $this->line('    - Roll back the social auth migration (drops columns)');
            $this->line('    - Remove social auth keys from .env');
            $this->line('');

            if (! $this->confirm('  Are you sure you want to continue?', false)) {
                $this->line('  Uninstall cancelled.');
                return self::SUCCESS;
            }

            $this->line('');
        }

        $this->removePublishedFiles();
        $this->removeEmptyControllerDirectory();

        if (! $this->option('skip-migration')) {
            $this->rollbackMigration();
        }

        if (! $this->option('skip-env')) {
            $this->removeEnvKeys();
        }

        $this->printFinalStep();

        return self::SUCCESS;
    }

    /**
     * Delete every file published by the install command.
     */
    protected function removePublishedFiles(): void
    {
        $this->info('  Removing published files...');

        foreach ($this->publishedFiles as $relativePath) {
            $fullPath = base_path($relativePath);

            if (! File::exists($fullPath)) {
                $this->line("  [skip] {$relativePath} — not found");
                continue;
            }

            // Warn if the controller has been customised
            if (
                str_contains($relativePath, 'SocialAuthController.php') &&
                ! $this->option('force') &&
                $this->fileHasCustomisations($fullPath)
            ) {
                if (! $this->confirm("  SocialAuthController.php appears to have custom code. Delete anyway?", false)) {
                    $this->line("  [skip] {$relativePath} — kept at your request");
                    continue;
                }
            }

            File::delete($fullPath);
            $this->line("  [removed] {$relativePath}");
        }

        // Remove any published migration files
        $this->removePublishedMigrationFiles();
    }

    /**
     * Remove any migration files published by the package.
     */
    protected function removePublishedMigrationFiles(): void
    {
        $migrations = File::glob(
            database_path('migrations/*_add_social_auth_columns_to_users_table.php')
        );

        foreach ($migrations as $path) {
            File::delete($path);
            $this->line('  [removed] ' . str_replace(base_path() . '/', '', $path));
        }
    }

    /**
     * Remove the Api/Auth directory if it is now empty.
     */
    protected function removeEmptyControllerDirectory(): void
    {
        $directory = app_path('Http/Controllers/Api/Auth');

        if (File::isDirectory($directory) && empty(File::files($directory))) {
            File::deleteDirectory($directory);
            $this->line('  [removed] app/Http/Controllers/Api/Auth/ (empty)');
        }

        // Also remove Api/ if now empty
        $apiDirectory = app_path('Http/Controllers/Api');

        if (File::isDirectory($apiDirectory) && empty(File::allFiles($apiDirectory))) {
            File::deleteDirectory($apiDirectory);
            $this->line('  [removed] app/Http/Controllers/Api/ (empty)');
        }
    }

    /**
     * Roll back the migration to drop the social auth columns.
     */
    protected function rollbackMigration(): void
    {
        $this->info('  Rolling back migration...');

        // Check whether columns actually exist before trying to roll back
        if (! Schema::hasColumn('users', 'provider')) {
            $this->line('  [skip] Migration columns not found — already rolled back or never ran');
            return;
        }

        try {
            $this->callSilently('migrate:rollback', [
                '--step' => 1,
            ]);

            $this->line('  [ok] Migration rolled back — social auth columns removed from users table');

        } catch (\Exception $e) {
            $this->error('  [error] Migration rollback failed: ' . $e->getMessage());
            $this->line('  Run manually: php artisan migrate:rollback --step=1');
        }
    }

    /**
     * Remove the social auth key block from .env.
     */
    protected function removeEnvKeys(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $this->info('  Removing .env keys...');

        $original = File::get($envPath);
        $updated  = $original;

        foreach ($this->envKeys as $key) {
            // Remove the key line regardless of whether it has a value
            $updated = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', '', $updated);
        }

        // Remove the section comment block inserted by the install command
        $updated = preg_replace(
            '/\n?# -{3,}\n# Laravel Social Auth\n# -{3,}\n/',
            '',
            $updated
        );

        // Collapse multiple blank lines left by the removal into a single blank line
        $updated = preg_replace('/\n{3,}/', "\n\n", $updated);

        if ($updated !== $original) {
            File::put($envPath, $updated);
            $this->line('  [ok] .env keys removed');
        } else {
            $this->line('  [skip] .env keys not found');
        }
    }

    /**
     * Determine whether the published controller contains user-written code
     * beyond the default stub (any non-comment, non-whitespace lines added).
     */
    protected function fileHasCustomisations(string $path): bool
    {
        $content = File::get($path);

        // If the file contains any method definitions beyond the class declaration,
        // it is likely customised.
        $methodCount = preg_match_all('/^\s+(public|protected|private)\s+function\s+/m', $content);

        return $methodCount > 0;
    }

    /**
     * Print the final manual step the developer must take.
     */
    protected function printFinalStep(): void
    {
        $this->line('');
        $this->line('  Uninstall complete.');
        $this->line('');
        $this->line('  One remaining step:');
        $this->line('');
        $this->line('    composer remove rahatulrabbi/laravel-social-auth');
        $this->line('');
        $this->line('  This removes the package from vendor/ and composer.json.');
        $this->line('');
    }
}