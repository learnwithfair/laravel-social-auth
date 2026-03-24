# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2024-01-01

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