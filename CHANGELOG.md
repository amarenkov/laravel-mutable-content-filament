# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.8.0] - 2026-10-13

### Added

- Money fields: a fieldset with the amount and the currency (a select of the `currency` LOV, or a hidden fixed currency shown as the amount suffix), validated by the core money rule; the column and the infolist show the amount with the currency symbol. Icon `OutlinedBanknotes`.
- Field type settings: «Allow negative values» and «Allow 0» for number and measurement types, with the default of the type (on for numbers and money, off for measurements); «Currency» for money. `describe()` shows them.

### Changed

- Number and measurement inputs follow the `allow_negative` and `allow_zero` settings (`RuleHelper::getSignRules()`); measurements no longer have a fixed minimum of 0.
- Requires `amarenkov/laravel-mutable-content` ^0.7.5.

## [0.7.2] - 2026-10-10

### Changed

- Date and time fields are shown in `mutable-content.timezone`; the form state stays in the application timezone, as Filament expects.
- Requires `amarenkov/laravel-mutable-content` ^0.7.3.

## [0.7.1] - 2026-10-10

### Added

- `datetime` fields: a date and time input in the application timezone, shown as `d.m.Y H:i`; a field type icon.

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.7.2.

## [0.7.0] - 2026-10-10

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.7.
- A field usage is bound to a class and optionally to one of its types (core 0.7 class types) instead of a class or a LOV: the type select lists the types of the chosen class, the LOV items are the `Item` class typed by LOV. Usage titles, the usage column and filter show the type, quick filters on the fields screen have a group per typed class.

## [0.6.1] - 2026-10-10

### Fixed

- A class label in a language other than English got an English plural ending ("Заказs"); outside English it is used as is.

## [0.6.0] - 2026-10-10

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.6.
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

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.8.0...HEAD
[0.8.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.7.2...v0.8.0
[0.7.2]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.6.1...v0.7.0
[0.6.1]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.5.1...v0.6.0
[0.5.1]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/amarenkov/laravel-mutable-content-filament/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-filament/releases/tag/v0.1.0
