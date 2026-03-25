# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] — 2026-03-25

### Added

- Dynamic name field mapping with two strategies: `single` (one column) and `split` (separate first and last name columns). Supports any column name such as `name`, `full_name`, `first_name`, `last_name`, `f_name`, `l_name`, and more.
- Dynamic avatar column mapping — the avatar column name is now configurable. Supports `avatar`, `avatar_path`, `image`, `profile_image`, `user_image`, or any custom column name.
- Multi-disk avatar storage — avatar images can now be stored via any Laravel filesystem disk (`local_public`, `public`, `s3`, or any disk defined in `config/filesystems.php`).
- Dynamic username column mapping with an enable/disable toggle. Set `username.enabled` to `false` to skip username handling entirely if your users table has no such column.
- Dynamic active status column mapping. The column name and written value are both configurable. Supports `is_active`, `status`, `active`, or any custom column name.
- Config-driven migration — the published migration now reads `config/social-auth.php` at runtime and creates only the columns that are enabled, using exactly the column names configured. No more hardcoded column names in the migration file.
- `user_model` config key to support User models outside the default `App\Models\User` namespace.

### Changed

- Migration is now fully dynamic and no longer requires manual editing before running.
- Install command next steps updated to reflect the field mapping configuration step that must be completed before running the migration.
- Laravel version detection in the install command now prints the correct Step 5 instruction based on whether Laravel 10 or 11+ is detected at runtime.

---

## [1.0.0] — 2026-03-01

### Added

- Google and Apple social login via Laravel Socialite
- Per-platform (Android / iOS) Google client ID resolution
- Automatic user provisioning with username generation
- Avatar download from social provider
- Laravel Sanctum token issuance
- `social-auth:install` Artisan command
- Publishable config, controller stub, and migration
- Automatic `.env` key injection
- Configurable route prefix, middleware, and enable/disable toggle
- Optional Plan model integration for free-plan assignment
- `ApiResponse` trait for consistent JSON structure