# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- A resource without its own model label takes the model's class label (`#[ClassLabel]`) instead of one made from the class name.
- Measurements are formatted with the separators of the current locale (core `NumberHelper`).

## [0.5.1] - 2026-10-10

### Changed

- Field type icons are kept in the package (`IconHelper::addLovItemIcons()`, `IconHelper::lovItemIcon()`) instead of the core LOV registry, so another UI package can have its own ones. An icon set for the item in the admin panel still takes precedence when it is a Heroicon.

### Added

- LOV items show their default icon in the table and as the icon field placeholder.

## [0.5.0] - 2026-10-09

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.5.
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

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.5.1...HEAD
[0.5.1]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-filament/releases/tag/v0.1.0
