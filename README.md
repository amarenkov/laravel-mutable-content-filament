# amarenkov/laravel-mutable-content-filament

[![tests](https://github.com/amarenkov/laravel-mutable-content-filament/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-filament/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-filament)](https://packagist.org/packages/amarenkov/laravel-mutable-content-filament)
![coverage](https://img.shields.io/badge/coverage-76%25-yellowgreen)

A Filament 5 admin panel for
[`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content).

Builds forms, infolists and tables from model field definitions and makes sure every change made
in the admin panel lands in the change log with its author.

## Features

- **Forms and tables from field definitions.** The component is chosen by field type: a toggle
  for booleans, a select for lists of values and object references, a date picker for dates,
  a text input by default. Required and immutable flags are applied automatically.
- **Change log.** Base pages and actions set the author and the path the change was made from;
  both go to the core change log table.
- **System records protection.** Deletion is hidden, and fields marked "immutable for system
  objects" are locked on records created by the seeders.
- **Management screens** for fields, their usage in classes, lists of values and their items:
  an administrator adds new fields without a developer and without migrations.
- **Unlisted codes.** Codes that are not in a list of values (when the field allows them) are
  marked in tables and forms, with a filter to find such records.

## Requirements

- PHP 8.4
- `amarenkov/laravel-mutable-content`
- `filament/filament` ^5.0

## Installation

```bash
composer require amarenkov/laravel-mutable-content-filament
```

The management screens are registered explicitly; resource discovery does not find them:

```php
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\UsageResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\LovResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\ItemResource;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->resources([
            FieldResource::class,
            UsageResource::class,
            LovResource::class,
            ItemResource::class,
        ]);
}
```

## Usage

Extend resources, pages and actions from this package instead of plain Filament ones:

```php
use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $recordTitleAttribute = 'code';

    public static function getPages(): array
    {
        return [
            'index'  => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view'   => ViewProject::route('/{record}'),
            'edit'   => EditProject::route('/{record}/edit'),
        ];
    }
}
```

```php
use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;
}
```

There is no need to define `form()`, `infolist()` or `table()`: they are built from the field
definitions.

### Tuning a single component

`formComponentsFromFields()` returns components keyed by field code, so you can adjust one
component without rebuilding the form:

```php
protected static function formComponentsFromFields(?array $scopes = null)
{
    $components = parent::formComponentsFromFields($scopes);

    $components['field_type']->live();

    $components['lov_code']->hidden(
        fn (Get $get) => $get('field_type') !== FieldType::TYPE_LOV_ITEM
    );

    return $components;
}
```

### Saving

Wrap custom saves in `SaveHelper::save()`: a unique constraint violation or a `DomainException`
thrown by the model is shown as a notification instead of a server error, and the transaction is
rolled back.

## Translations

UI strings are in `lang/{en,ru}/ui.php` under the `mutable-content-filament` namespace. Publish
them to override:

```bash
php artisan vendor:publish --tag=mutable-content-filament-lang
```

## Caveats

Extending plain Filament classes instead of the ones from this package does not throw, but
silently breaks three things: forms get raw `jsonb` instead of flat fields, the change log loses
the author, and deletion of system records is no longer blocked.

## License

MIT. See [LICENSE](LICENSE).
