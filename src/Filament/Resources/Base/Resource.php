<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Base;

use Closure;
use InvalidArgumentException;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\ComponentAttributeBag;

use Filament\Resources\Resource as BaseResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\generate_icon_html;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as DomainFieldType;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassDescription;

use Amarenkov\MutableContent\Helpers\DatabaseHelper;
use Amarenkov\MutableContent\Helpers\ObjectHelper;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Density;
use Amarenkov\MutableContent\ValueObjects\SurfaceDensity;

use Amarenkov\MutableContentFilament\Helpers\IconHelper;

use Amarenkov\MutableContentFilament\Filament\Actions\EditAction;
use Amarenkov\MutableContentFilament\Filament\Actions\DeleteAction;

class Resource extends BaseResource
{
    // const
    protected const DATE_DISPLAY_FORMAT = 'd.m.Y';

    protected const UNLISTED_CODE_HINT = 'mutable-content-filament::ui.unlisted_code';

    // static
    protected static ?string $recordTitleAttribute = Field::COMMON_CODE_LABEL;

    protected static bool $hasTitleCaseModelLabel = false;

    /**
     * The model's class label (#[ClassLabel]) unless the resource sets its own.
     */
    public static function getModelLabel(): string
    {
        if (static::$modelLabel === null && static::getLabel() === null && ($label = static::classLabel()) !== null) {
            return $label;
        }

        return parent::getModelLabel();
    }

    /**
     * The class label is pluralized in English only: Str::plural() knows no other language.
     */
    public static function getPluralModelLabel(): string
    {
        if (static::$pluralModelLabel === null && static::getPluralLabel() === null && static::$modelLabel === null && static::getLabel() === null && ($label = static::classLabel()) !== null) {
            return str_starts_with(app()->getLocale(), 'en') ? Str::plural($label) : $label;
        }

        return parent::getPluralModelLabel();
    }

    protected static function classLabel(): ?string
    {
        $model = static::getModel();

        if (!is_subclass_of($model, ModelWithFields::class)) {
            return null;
        }

        try {
            return new MutableClassDescription($model)->label;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Field usage scopes of the page (see ModelWithFields::getFieldScopes()); null for the class ones.
     *
     * @return ?array<string>
     */
    protected static function getFieldScopes(mixed $livewire): ?array
    {
        return null;
    }

    protected static function formComponentsFromFields(?array $scopes = null)
    {
        $components = [];

        $lovRegistry = app(LovRegistry::class);

        $fields = static::$model::getFieldDefinitions($scopes);

        $maxLengths = static::$model::getFieldMaxLengths();

        foreach ($fields as $field) {
            $usage = $field->usage();

            if ($usage->isImmutable || $field->fieldType === DomainFieldType::TYPE_SYSTEM) {
                continue;
            }

            switch ($field->fieldType) {
                case DomainFieldType::TYPE_BOOL:
                    $component = Toggle::make($field->code)->inline(false);
                    break;

                case DomainFieldType::TYPE_TEXT:
                    $component = Textarea::make($field->code)
                        ->rows(3)
                        ->autosize()
                        ->columnSpanFull();
                    break;

                case DomainFieldType::TYPE_FLOAT:
                    $component = TextInput::make($field->code)
                        ->numeric()
                        ->step('any');
                    break;

                case DomainFieldType::TYPE_INT:
                    $component = TextInput::make($field->code)->integer();
                    break;

                case DomainFieldType::TYPE_LOV:
                    $component = Select::make($field->code)->options($lovRegistry->getLovsOptions());
                    break;

                case DomainFieldType::TYPE_LOV_ITEM:
                    $component = static::lovItemFormComponent($field);
                    break;

                case DomainFieldType::TYPE_OBJECT:
                    $component = static::objectFormComponent($field);
                    break;

                case DomainFieldType::TYPE_WEIGHT:
                case DomainFieldType::TYPE_LENGTH:
                case DomainFieldType::TYPE_AREA:
                case DomainFieldType::TYPE_VOLUME:
                    $component = static::unitFormComponent($field);
                    break;

                case DomainFieldType::TYPE_ICON:
                    $component = static::iconFormComponent($field);
                    break;

                case DomainFieldType::TYPE_DATE:
                    $component = DatePicker::make($field->code)
                        ->native(false)
                        ->displayFormat(static::DATE_DISPLAY_FORMAT)
                        ->format('Y-m-d');
                    break;

                case DomainFieldType::TYPE_DENSITY:
                    $component = TextInput::make($field->code)
                        ->numeric()
                        ->rule(TypeSettings::allowsZero($field) ? 'min:0' : 'gt:0')
                        ->step('any')
                        ->suffix(__('mutable-content::units.kg_per_m3'));
                    break;

                case DomainFieldType::TYPE_SURFACE_DENSITY:
                    $component = TextInput::make($field->code)
                        ->numeric()
                        ->rule(TypeSettings::allowsZero($field) ? 'min:0' : 'gt:0')
                        ->step('any')
                        ->suffix(__('mutable-content::units.kg_per_m2'));
                    break;

                default:
                    $component = TextInput::make($field->code);
                    break;
            }

            $component->label($field->label);

            if (isset($maxLengths[$field->code]) && ($component instanceof TextInput || $component instanceof Textarea)) {
                $component->maxLength($maxLengths[$field->code]);
            }

            if ($usage->isRequired) {
                $component->required();
            }

            if ($usage->isImmutableForSystemObjects) {
                $component->disabled(function($record) {
                    return $record && $record->isSystem();
                });
            }   

            $components[$component->getName()] = $component;
        }

        return $components;
    }

    protected static function infolistComponentsFromFields(?array $scopes = null)
    {
        $components = [];

        $lovRegistry = app(LovRegistry::class);

        $fields = static::$model::getFieldDefinitions($scopes);

        $maxLengths = static::$model::getFieldMaxLengths();

        foreach ($fields as $field) {
            $usage = $field->usage();

            if ($usage->isImmutable || $field->fieldType === DomainFieldType::TYPE_SYSTEM) {
                continue;
            }

            switch ($field->fieldType) {
                case DomainFieldType::TYPE_TEXT:
                    $component = TextEntry::make($field->code)
                        ->formatStateUsing(fn ($state) => nl2br(e($state)))
                        ->html()
                        ->columnSpanFull();
                    break;

                case DomainFieldType::TYPE_LOV_ITEM:
                    $component = TextEntry::make($field->code)->formatStateUsing(fn ($state) => static::lovItemTitle($field, $state));
                    break;

                case DomainFieldType::TYPE_OBJECT:
                    $component = TextEntry::make($field->code);

                    if (TypeSettings::linksByCode($field)) {
                        $component->formatStateUsing(fn ($state) => static::objectCodeTitle($field, $state, ObjectHelper::getTitleByCode($field->objectClass, $state)));
                    } elseif ($field->objectClass) {
                        $component->formatStateUsing(function ($state) use ($field) {
                            return ObjectHelper::getTitleById($field->objectClass, $state) ?? $state;
                        });
                    }
                    break;

                case DomainFieldType::TYPE_WEIGHT:
                case DomainFieldType::TYPE_LENGTH:
                case DomainFieldType::TYPE_AREA:
                case DomainFieldType::TYPE_VOLUME:
                    $component = TextEntry::make($field->code)->formatStateUsing(static::unitFormatter($field));
                    break;

                case DomainFieldType::TYPE_DATE:
                    $component = TextEntry::make($field->code)->date(static::DATE_DISPLAY_FORMAT);
                    break;

                case DomainFieldType::TYPE_ICON:
                    $component = IconEntry::make($field->code)
                        ->icon(fn ($state) => IconHelper::getIcon($state))
                        ->tooltip(fn ($record) => $record ? ObjectHelper::getTitle($record) : null);
                    break;

                case DomainFieldType::TYPE_DENSITY:
                    $component = TextEntry::make($field->code)->formatStateUsing(function ($state) {
                        try {
                            return Density::fromFieldValue($state)?->format();
                        } catch (InvalidArgumentException) {
                            return $state;
                        }
                    });
                    break;

                case DomainFieldType::TYPE_SURFACE_DENSITY:
                    $component = TextEntry::make($field->code)->formatStateUsing(function ($state) {
                        try {
                            return SurfaceDensity::fromFieldValue($state)?->format();
                        } catch (InvalidArgumentException) {
                            return $state;
                        }
                    });
                    break;

                default:
                    $component = TextEntry::make($field->code);
                    break;
            }

            $component->label($field->label);

            $components[$component->getName()] = $component;
        }

        return $components;
    }

    protected static function tableFromFields(Table $table, ?array $scopes = null)
    {
        $columns = [];

        $lovRegistry = app(LovRegistry::class);

        $parentModel = static::getParentResourceRegistration()?->getParentResource()::getModel();

        $fields = static::$model::getFieldDefinitions($scopes);

        $iconColumns = [];

        $systemMarkInCode = isset($fields[Field::COMMON_CODE_CODE], $fields[Field::COMMON_CODE_IS_SYSTEM]);

        foreach ($fields as $field) {
            if ($field->fieldType === DomainFieldType::TYPE_SYSTEM) {
                continue;
            }

            if ($systemMarkInCode && $field->code === Field::COMMON_CODE_IS_SYSTEM) {
                continue;
            }

            if ($parentModel && $field->fieldType === DomainFieldType::TYPE_OBJECT && $field->objectClass === $parentModel) {
                continue;
            }

            $column = null;

            switch ($field->fieldType) {
                case DomainFieldType::TYPE_BOOL:
                    $column = IconColumn::make($field->code)->boolean()->falseIcon(false);
                    break;

                case DomainFieldType::TYPE_FLOAT:
                    $column = TextColumn::make($field->code)->numeric();
                    break;

                case DomainFieldType::TYPE_INT:
                    $column = TextColumn::make($field->code)->numeric();
                    break;

                case DomainFieldType::TYPE_TEXT:
                    $column = TextColumn::make($field->code)
                        ->limit(50)
                        ->tooltip(function (TextColumn $column) {
                            $state = $column->getState();

                            return is_string($state) && mb_strlen($state) > $column->getCharacterLimit() ? $state : null;
                        });
                    break;

                case DomainFieldType::TYPE_LOV:
                    $column = TextColumn::make($field->code)->formatStateUsing(function (string $state) use ($lovRegistry) {
                        return $state ? $lovRegistry->getLovLabel($state) : null;
                    });
                    break;

                case DomainFieldType::TYPE_LOV_ITEM:
                    $column = TextColumn::make($field->code)->formatStateUsing(fn ($state) => static::lovItemTitle($field, $state));
                    break;

                case DomainFieldType::TYPE_OBJECT:
                    $column = static::objectColumn($field);
                    break;

                case DomainFieldType::TYPE_WEIGHT:
                case DomainFieldType::TYPE_LENGTH:
                case DomainFieldType::TYPE_AREA:
                case DomainFieldType::TYPE_VOLUME:
                    $column = TextColumn::make($field->code)->formatStateUsing(static::unitFormatter($field));
                    break;

                case DomainFieldType::TYPE_DATE:
                    $column = TextColumn::make($field->code)->date(static::DATE_DISPLAY_FORMAT);
                    break;

                case DomainFieldType::TYPE_ICON:
                    $column = IconColumn::make($field->code)
                        ->icon(fn ($state) => IconHelper::getIcon($state))
                        ->tooltip(fn ($record) => $record ? ObjectHelper::getTitle($record) : null);
                    break;

                case DomainFieldType::TYPE_DENSITY:
                    $column = TextColumn::make($field->code)
                        ->numeric(maxDecimalPlaces: 3)
                        ->suffix(' '.__('mutable-content::units.kg_per_m3'));
                    break;

                case DomainFieldType::TYPE_SURFACE_DENSITY:
                    $column = TextColumn::make($field->code)
                        ->numeric(maxDecimalPlaces: 3)
                        ->suffix(' '.__('mutable-content::units.kg_per_m2'));
                    break;

                default:
                    $column = TextColumn::make($field->code);
                    break;
            }

            if (in_array($field->code, [Field::COMMON_CODE_CODE, Field::COMMON_CODE_LABEL], true)) {
                static::sortableAndSearchable($column, $field->code);
            }

            if ($systemMarkInCode && $field->code === Field::COMMON_CODE_CODE) {
                $column->formatStateUsing(fn ($state, $record) => static::codeWithSystemMark($state, $record, $fields[Field::COMMON_CODE_IS_SYSTEM]));
            }

            if ($field->fieldType === DomainFieldType::TYPE_ICON) {
                $column->label($field->label)
                    ->toggleable()
                    ->extraHeaderAttributes(['style' => 'font-size: 0;'])
                    ->width('1%');

                $iconColumns[] = $column;

                continue;
            }

            $column->label($field->label)
                ->wrapHeader()
                ->toggleable(isToggledHiddenByDefault: $field->fieldType === DomainFieldType::TYPE_TEXT);

            $columns[] = $column;
        }

        $columns = [...$iconColumns, ...$columns];

        $filters = [];

        if (isset($fields[Field::COMMON_CODE_IS_SYSTEM])) {
            $filters[] = static::isSystemFilter($fields[Field::COMMON_CODE_IS_SYSTEM]);
        }

        if ($unlistedCodesFilter = static::unlistedCodesFilter($fields)) {
            $filters[] = $unlistedCodesFilter;
        }

        return $table
            ->columns($columns)
            ->filters($filters);
    }

    protected static function displayUnit(Field $field): string
    {
        return TypeSettings::getValue($field, TypeSettings::DISPLAY_UNIT) ?? TypeSettings::getUnitClass($field->fieldType)::baseUnit();
    }

    protected static function unitFormComponent(Field $field)
    {
        $class = TypeSettings::getUnitClass($field->fieldType);
        $unit = static::displayUnit($field);

        $component = TextInput::make($field->code)
            ->numeric()
            ->minValue(0)
            ->step('any')
            ->suffix($class::unitLabel($unit));

        if (in_array($field->fieldType, TypeSettings::ALLOW_ZERO_TYPES, true) && !TypeSettings::allowsZero($field)) {
            $component->rule('gt:0');
        }

        if ($unit === $class::baseUnit()) {
            return $component;
        }

        return $component
            ->formatStateUsing(function ($state) use ($class, $unit) {
                try {
                    return $class::fromFieldValue($state)?->toUnit($unit);
                } catch (InvalidArgumentException) {
                    return $state;
                }
            })
            ->dehydrateStateUsing(function ($state) use ($class, $unit) {
                return is_numeric($state) ? $class::fromUnit($state, $unit)->toFieldValue() : $state;
            });
    }

    protected static function lovItemFormComponent(Field $field): Select
    {
        $lovRegistry = app(LovRegistry::class);

        $withIcons = false;
        $options = [];

        foreach ($lovRegistry->getLovItemsOptions($field->lovCode) as $code => $label) {
            $icon = IconHelper::lovItemIcon($field->lovCode, $code);
            $withIcons = $withIcons || $icon !== null;

            $options[$code] = [$label, $icon];
        }

        $render = function (array $options) use ($withIcons): array {
            return array_map(function (array $option) use ($withIcons) {
                [$label, $icon] = $option;

                if (!$withIcons) {
                    return $label;
                }

                $iconHtml = $icon ? generate_icon_html($icon, size: IconSize::Small)?->toHtml() : '<span style="display: inline-block; width: 1rem;"></span>';

                return '<span style="display: inline-flex; align-items: center; gap: 0.375rem;">'.$iconHtml.e((string)$label).'</span>';
            }, $options);
        };

        $component = Select::make($field->code);

        if ($withIcons) {
            $component->native(false)->allowHtml();
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return $component->options($render($options));
        }

        return $component->options(function (Select $component) use ($field, $lovRegistry, $options, $render) {
            $state = $component->getState();

            if (filled($state) && !$lovRegistry->hasLovItem($field->lovCode, $state)) {
                $options = [$state => [$state.' ('.__(static::UNLISTED_CODE_HINT).')', null]] + $options;
            }

            return $render($options);
        });
    }

    protected static function lovItemTitle(Field $field, $state): string|HtmlString|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        $lovRegistry = app(LovRegistry::class);

        if ($lovRegistry->hasLovItem($field->lovCode, $state)) {
            $label = $lovRegistry->getLovItemLabel($field->lovCode, $state);

            if ($icon = IconHelper::lovItemIcon($field->lovCode, $state)) {
                return new HtmlString('<span style="display: inline-flex; align-items: center; gap: 0.375rem;">'.generate_icon_html($icon, size: IconSize::Small)?->toHtml().e((string)$label).'</span>');
            }

            return $label;
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return $lovRegistry->getLovItemLabel($field->lovCode, $state);
        }

        return static::textWithMarks((string)$state, [[Heroicon::OutlinedExclamationTriangle, __(static::UNLISTED_CODE_HINT), 'danger']]);
    }

    protected static function iconFormComponent(Field $field)
    {
        return Select::make($field->code)
            ->searchable()
            ->allowHtml()
            ->options(fn () => IconHelper::getOptions())
            ->getSearchResultsUsing(fn (?string $search) => IconHelper::getOptions($search))
            ->getOptionLabelUsing(fn ($value) => ($icon = IconHelper::getIcon($value)) ? IconHelper::getOptionLabel($icon) : $value)
            ->rule(Rule::in(IconHelper::getValues()));
    }

    protected static function unitFormatter(Field $field)
    {
        $class = TypeSettings::getUnitClass($field->fieldType);
        $unit = static::displayUnit($field);

        return function ($state) use ($class, $unit) {
            try {
                return $class::fromFieldValue($state)?->format(unit: $unit);
            } catch (InvalidArgumentException) {
                return $state;
            }
        };
    }

    protected static function objectFormComponent(Field $field)
    {
        if (!$field->objectClass) {
            return TextInput::make($field->code)->integer();
        }

        $class = $field->objectClass;

        if (TypeSettings::linksByCode($field)) {
            return Select::make($field->code)
                ->options(fn () => ObjectHelper::getOptions($class, byCode: true))
                ->searchable()
                ->getSearchResultsUsing(fn (?string $search) => ObjectHelper::getOptions($class, $search, byCode: true))
                ->getOptionLabelUsing(function ($value) use ($class, $field) {
                    $title = ObjectHelper::getTitleByCode($class, $value);

                    return $title ?? (TypeSettings::allowsUnlistedCodes($field) ? $value.' ('.__(static::UNLISTED_CODE_HINT).')' : null);
                });
        }

        return Select::make($field->code)
            ->options(fn () => ObjectHelper::getOptions($class))
            ->searchable()
            ->getSearchResultsUsing(fn (?string $search) => ObjectHelper::getOptions($class, $search))
            ->getOptionLabelUsing(fn ($value) => ObjectHelper::getTitleById($class, $value))
            ->dehydrateStateUsing(fn ($state) => ObjectHelper::toId($state));
    }

    protected static function objectCodeTitle(Field $field, $state, ?string $title): string|HtmlString|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($title !== null) {
            return $title;
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return (string)$state;
        }

        return static::textWithMarks((string)$state, [[Heroicon::OutlinedExclamationTriangle, __(static::UNLISTED_CODE_HINT), 'danger']]);
    }

    protected static function objectColumn(Field $field)
    {
        $column = TextColumn::make($field->code);

        if (!$field->objectClass) {
            return $column->numeric();
        }

        $titles = null;

        if (TypeSettings::linksByCode($field)) {
            return $column->formatStateUsing(function ($state, HasTable $livewire) use ($field, &$titles) {
                $titles ??= ObjectHelper::getTitlesByCodes(
                    $field->objectClass,
                    $livewire->getTableRecords()->pluck($field->code)->all()
                );

                return static::objectCodeTitle($field, $state, $titles[(string)$state] ?? null);
            });
        }

        return $column->formatStateUsing(function ($state, HasTable $livewire) use ($field, &$titles) {
            if ($titles === null) {
                $titles = ObjectHelper::getTitles(
                    $field->objectClass,
                    $livewire->getTableRecords()->pluck($field->code)->all()
                );
            }

            return $titles[ObjectHelper::toId($state)] ?? $state;
        });
    }

    protected static function objectFilter(Field $field)
    {
        $filter = SelectFilter::make($field->code)
            ->label($field->label);

        if (!$field->objectClass) {
            return $filter;
        }

        $class = $field->objectClass;

        if (TypeSettings::linksByCode($field)) {
            return $filter
                ->options(fn () => ObjectHelper::getOptions($class, byCode: true))
                ->searchable()
                ->getSearchResultsUsing(fn (?string $search) => ObjectHelper::getOptions($class, $search, byCode: true))
                ->getOptionLabelUsing(fn ($value) => ObjectHelper::getTitleByCode($class, $value) ?? $value);
        }

        return $filter
            ->options(fn () => ObjectHelper::getOptions($class))
            ->searchable()
            ->getSearchResultsUsing(fn (?string $search) => ObjectHelper::getOptions($class, $search))
            ->getOptionLabelUsing(fn ($value) => ObjectHelper::getTitleById($class, $value));
    }

    protected static function sortableAndSearchable(TextColumn $column, string $code): TextColumn
    {
        return $column
            ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('fields->'.$code, $direction))
            ->searchable(query: fn (Builder $query, string $search) => DatabaseHelper::whereLike($query, 'fields->'.$code, '%'.addcslashes($search, '%_\\').'%'));
    }

    protected static function codeWithSystemMark($state, $record, Field $isSystemField): HtmlString
    {
        return static::textWithMarks((string)$state, $record?->isSystem() ? [[Heroicon::OutlinedLockClosed, $isSystemField->label]] : []);
    }

    /**
     * Text followed by icons with tooltips.
     *
     * @param array<array{0: Heroicon, 1: ?string, 2?: string}> $marks icon, tooltip and color (gray by default)
     */
    protected static function textWithMarks(string $text, array $marks): HtmlString
    {
        $html = e($text);

        foreach ($marks as $mark) {
            [$icon, $title] = $mark;
            $color = isset($mark[2]) ? 'var(--'.$mark[2].'-500)' : 'var(--gray-400)';

            $iconHtml = generate_icon_html($icon, attributes: new ComponentAttributeBag(['style' => 'color: '.$color.';']), size: IconSize::Small)?->toHtml();

            $html .= ' <span title="'.e((string)$title).'" style="display: inline-flex; vertical-align: middle;">'.$iconHtml.'</span>';
        }

        return new HtmlString($html);
    }

    protected static function fieldsSelectFilter(string $code, string $label, Closure $options): SelectFilter
    {
        return SelectFilter::make($code)
            ->label($label)
            ->options($options)
            ->searchable()
            ->query(function (Builder $query, array $data) use ($code) {
                return filled($data['value'] ?? null) ? $query->where('fields->'.$code, $data['value']) : $query;
            });
    }

    /**
     * Records with an unlisted code in any field that allows such codes; null if there are no such fields.
     *
     * @param array<Field> $fields
     */
    protected static function unlistedCodesFilter(array $fields): ?TernaryFilter
    {
        $codeFields = array_filter($fields, fn (Field $field) => in_array($field->fieldType, [DomainFieldType::TYPE_LOV_ITEM, DomainFieldType::TYPE_OBJECT], true) && TypeSettings::allowsUnlistedCodes($field));

        if (!$codeFields) {
            return null;
        }

        $hasUnlisted = function (Builder $query) use ($codeFields) {
            $lovRegistry = app(LovRegistry::class);

            foreach ($codeFields as $field) {
                $column = 'fields->'.$field->code;

                $codes = $field->fieldType === DomainFieldType::TYPE_OBJECT
                    ? ObjectHelper::codesQuery($field->objectClass)
                    : array_map('strval', array_keys($lovRegistry->getLovItemsOptions($field->lovCode)));

                $query->orWhere(fn (Builder $query) => $query->whereNotNull($column)->whereNotIn($column, $codes));
            }
        };

        return TernaryFilter::make('unlisted_lov_codes')
            ->label(__('mutable-content-filament::ui.unlisted_codes_filter'))
            ->trueLabel(__('mutable-content-filament::ui.filters.with_them'))
            ->falseLabel(__('mutable-content-filament::ui.filters.without_them'))
            ->queries(
                true: fn (Builder $query) => $query->where($hasUnlisted),
                false: fn (Builder $query) => $query->whereNot($hasUnlisted),
            );
    }

    protected static function isSystemFilter(Field $field)
    {
        return static::boolFilter($field)
            ->trueLabel(__('mutable-content-filament::ui.filters.system_only'))
            ->falseLabel(__('mutable-content-filament::ui.filters.non_system_only'));
    }

    protected static function boolFilter(Field $field): TernaryFilter
    {
        $column = 'fields->'.$field->code;

        return TernaryFilter::make($field->code)
            ->label($field->label)
            ->queries(
                true: fn (Builder $query) => $query->where($column, true),
                false: fn (Builder $query) => $query->where(function (Builder $query) use ($column) {
                    $query->whereNull($column)->orWhere($column, false);
                }),
            );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(array_values(static::formComponentsFromFields(static::getFieldScopes($schema->getLivewire()))));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(array_values(static::infolistComponentsFromFields(static::getFieldScopes($schema->getLivewire()))));
    }

    public static function recordActions()
    {
        return [
            DeleteAction::make()
                ->hiddenLabel(),

            EditAction::make()
                ->hiddenLabel(),
        ];
    }

    public static function table(Table $table): Table
    {
        return static::tableFromFields($table, static::getFieldScopes($table->getLivewire()))
            ->recordTitleAttribute(Field::COMMON_CODE_LABEL)
            ->recordActions(static::recordActions())
            ->toolbarActions([
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
