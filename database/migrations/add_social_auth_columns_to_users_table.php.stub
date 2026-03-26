<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration reads your published config/social-auth.php at runtime
 * to determine the exact column names to create.
 *
 * IMPORTANT: Publish and configure config/social-auth.php BEFORE running
 * this migration. All column names, enabled/disabled flags, and feature
 * toggles are respected automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ------------------------------------------------------------------
            // Provider fields — always required regardless of any config toggle
            // ------------------------------------------------------------------

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique();
            }

            if (! Schema::hasColumn('users', 'password')) {
                $table->string('password')->after('email');
            }

            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }

            // ------------------------------------------------------------------
            // Name field
            // Reads: social-auth.name_field.strategy
            //        social-auth.name_field.column   (strategy = single)
            //        social-auth.name_field.first     (strategy = split)
            //        social-auth.name_field.last      (strategy = split)
            // ------------------------------------------------------------------

            $nameStrategy = config('social-auth.name_field.strategy', 'single');

            if ($nameStrategy === 'split') {
                $firstCol = config('social-auth.name_field.first', 'first_name');
                $lastCol  = config('social-auth.name_field.last',  'last_name');

                if (! Schema::hasColumn('users', $firstCol)) {
                    $table->string($firstCol)->nullable()->after('email');
                }

                if (! Schema::hasColumn('users', $lastCol)) {
                    $table->string($lastCol)->nullable()->after($firstCol);
                }
            } else {
                // 'single' strategy — Laravel's default users table already has
                // a 'name' column. Only create it if the configured column is
                // different and does not yet exist.
                $nameCol = config('social-auth.name_field.column', 'name');

                if ($nameCol !== 'name' && ! Schema::hasColumn('users', $nameCol)) {
                    $table->string($nameCol)->nullable()->after('email');
                }
            }

            // ------------------------------------------------------------------
            // Username field
            // Reads: social-auth.username.enabled
            //        social-auth.username.column
            // ------------------------------------------------------------------

            if (config('social-auth.username.enabled', true)) {
                $usernameCol = config('social-auth.username.column', 'username');

                if (! Schema::hasColumn('users', $usernameCol)) {
                    $table->string($usernameCol)->nullable()->unique()->after('email');
                }
            }

            // ------------------------------------------------------------------
            // Avatar field
            // Reads: social-auth.avatar.enabled
            //        social-auth.avatar.column
            // ------------------------------------------------------------------

            if (config('social-auth.avatar.enabled', true)) {
                $avatarCol = config('social-auth.avatar.column', 'avatar_path');

                if (! Schema::hasColumn('users', $avatarCol)) {
                    $table->string($avatarCol)->nullable()->after('provider_id');
                }
            }

            // ------------------------------------------------------------------
            // role field
            // Reads: social-auth.role.enabled
            //        social-auth.role.column
            // ------------------------------------------------------------------

            if (config('social-auth.role.enabled', true)) {
                $roleCol = config('social-auth.role.column', 'role');

                if (! Schema::hasColumn('users', $roleCol)) {
                    $table->string($roleCol)->nullable()->after('password');
                }
            }

            // ------------------------------------------------------------------
            // Active status field
            // Reads: social-auth.active_status.enabled
            //        social-auth.active_status.column
            //        social-auth.active_status.value
            // ------------------------------------------------------------------

            if (config('social-auth.active_status.enabled', true)) {
                $activeCol   = config('social-auth.active_status.column', 'is_active');
                $activeValue = config('social-auth.active_status.value', true);

                if (! Schema::hasColumn('users', $activeCol)) {
                    $table->boolean($activeCol)->default($activeValue ? 1 : 0)->after('provider_id');
                }
            }  

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('provider_id');
            }
                     
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Collect all columns this migration may have created
            $columns = ['provider', 'provider_id'];

            $nameStrategy = config('social-auth.name_field.strategy', 'single');

            if ($nameStrategy === 'split') {
                $columns[] = config('social-auth.name_field.first', 'first_name');
                $columns[] = config('social-auth.name_field.last',  'last_name');
            } else {
                $nameCol = config('social-auth.name_field.column', 'name');
                if ($nameCol !== 'name') {
                    $columns[] = $nameCol;
                }
            }

            if (config('social-auth.username.enabled', true)) {
                $columns[] = config('social-auth.username.column', 'username');
            }

            if (config('social-auth.role.enabled', true)) {
                $columns[] = config('social-auth.role.column', 'role');
            }

            if (config('social-auth.avatar.enabled', true)) {
                $columns[] = config('social-auth.avatar.column', 'avatar_path');
            }

            if (config('social-auth.active_status.enabled', true)) {
                $columns[] = config('social-auth.active_status.column', 'is_active');
            }            

            foreach (array_unique($columns) as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

        });
    }
};
