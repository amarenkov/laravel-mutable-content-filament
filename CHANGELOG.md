# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Pages and actions set the change log author and comment with `withLogContext()` instead of `setUpdatedByIfDirty()`, removed in the core 0.5.

## [0.4.0] - 2026-10-09

### Added

- MariaDB support: table search is case-insensitive through `DatabaseHelper::whereLike()`, and the usage table sorts by scope without `nulls last`.

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.4.

## [0.3.0] - 2026-10-08

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.3.

## [0.2.0] - 2026-10-08

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.2.

## [0.1.0] - 2026-10-08

Initial release.

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-filament/releases/tag/v0.1.0
