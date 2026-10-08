<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields;

use ArrayAccess;
use Closure;

use Filament\Forms\Components\Field as FormField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as FieldType;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;

use Amarenkov\MutableContent\Models\ModelWithFields;

/**
 * Field type settings components for the field and usage forms.
 */
class FieldTypeSettingsComponents
{
    // const
    public const DISPLAY_UNIT_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::DISPLAY_UNIT;
    public const ALLOW_UNLISTED_CODES_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::ALLOW_UNLISTED_CODES;
    public const ALLOW_ZERO_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::ALLOW_ZERO;
    public const LINK_BY_CODE_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::LINK_BY_CODE;

    protected const LINK_BY_CODE_LABEL = 'mutable-content-filament::ui.settings.link_by_code';
    protected const LINK_BY_CODE_HELPER = 'mutable-content-filament::ui.settings.link_by_code_helper';

    protected const ALLOW_UNLISTED_CODES_LABEL = 'mutable-content-filament::ui.settings.allow_unlisted_codes';
    protected const ALLOW_UNLISTED_CODES_HELPER = 'mutable-content-filament::ui.settings.allow_unlisted_codes_helper';
    protected const ALLOW_UNLISTED_CODES_OBJECT_HELPER = 'mutable-content-filament::ui.settings.allow_unlisted_codes_object_helper';

    protected const ALLOW_ZERO_LABEL = 'mutable-content-filament::ui.settings.allow_zero';
    protected const ALLOW_ZERO_HELPER = 'mutable-content-filament::ui.settings.allow_zero_helper';

    // static
    /**
     * @param Closure $fieldType field type to show the settings for, evaluated by Filament
     * @param ?Closure $inherited overridden settings (the field ones for a usage); null if none
     *
     * @return array<string, \Filament\Forms\Components\Field>
     */
    public static function make(Closure $fieldType, ?Closure $inherited = null): array
    {
        return static::displayUnitComponents($fieldType, $inherited)
            + static::linkByCodeComponents($fieldType, $inherited)
            + static::allowUnlistedCodesComponents($fieldType, $inherited)
            + static::allowZeroComponents($fieldType, $inherited);
    }

    protected static function flagComponent(string $setting, string $statePath, string $label, string|Closure $helper, Closure $visible, ?Closure $inherited): FormField
    {
        $recordValue = fn (?ModelWithFields $record) => $record?->getField(Field::CODE_FIELD_TYPE_SETTINGS)[$setting] ?? null;

        if ($inherited) {
            return Select::make($statePath)
                ->label($label)
                ->helperText($helper)
                ->options(['1' => __('mutable-content-filament::ui.yes'), '0' => __('mutable-content-filament::ui.no')])
                ->placeholder(fn (Select $component) => __('mutable-content-filament::ui.settings.inherited', ['value' => ($component->evaluate($inherited)[$setting] ?? null) === true ? __('mutable-content-filament::ui.yes_lower') : __('mutable-content-filament::ui.no_lower')]))
                ->formatStateUsing(fn (?ModelWithFields $record) => match ($recordValue($record)) {
                    true => '1',
                    false => '0',
                    default => null,
                })
                ->dehydrateStateUsing(fn ($state) => match ((string)$state) {
                    '1' => true,
                    '0' => false,
                    default => null,
                })
                ->visible($visible);
        }

        return Toggle::make($statePath)
            ->label($label)
            ->helperText($helper)
            ->inline(false)
            ->formatStateUsing(fn (?ModelWithFields $record) => $recordValue($record) === true)
            ->dehydrateStateUsing(fn ($state) => $state ? true : null)
            ->visible($visible);
    }

    protected static function allowZeroComponents(Closure $fieldType, ?Closure $inherited): array
    {
        $component = static::flagComponent(
            TypeSettings::ALLOW_ZERO,
            self::ALLOW_ZERO_STATE_PATH,
            __(self::ALLOW_ZERO_LABEL),
            __(self::ALLOW_ZERO_HELPER),
            fn (FormField $component) => in_array((string)$component->evaluate($fieldType), TypeSettings::ALLOW_ZERO_TYPES, true),
            $inherited,
        );

        return [$component->getName() => $component];
    }

    protected static function linkByCodeComponents(Closure $fieldType, ?Closure $inherited): array
    {
        $component = static::flagComponent(
            TypeSettings::LINK_BY_CODE,
            self::LINK_BY_CODE_STATE_PATH,
            __(self::LINK_BY_CODE_LABEL),
            __(self::LINK_BY_CODE_HELPER),
            fn (FormField $component) => (string)$component->evaluate($fieldType) === FieldType::TYPE_OBJECT,
            $inherited,
        );

        $component->live();

        return [$component->getName() => $component];
    }

    protected static function linksByCodeInForm(FormField $component, Get $get, ?Closure $inherited): bool
    {
        $state = $get(self::LINK_BY_CODE_STATE_PATH);

        if ($state === null || $state === '') {
            return $inherited && ($component->evaluate($inherited)[TypeSettings::LINK_BY_CODE] ?? null) === true;
        }

        return $state === true || (string)$state === '1';
    }

    protected static function allowUnlistedCodesComponents(Closure $fieldType, ?Closure $inherited): array
    {
        $component = static::flagComponent(
            TypeSettings::ALLOW_UNLISTED_CODES,
            self::ALLOW_UNLISTED_CODES_STATE_PATH,
            __(self::ALLOW_UNLISTED_CODES_LABEL),
            fn (FormField $component) => (string)$component->evaluate($fieldType) === FieldType::TYPE_OBJECT ? __(self::ALLOW_UNLISTED_CODES_OBJECT_HELPER) : __(self::ALLOW_UNLISTED_CODES_HELPER),
            fn (FormField $component, Get $get) => match ((string)$component->evaluate($fieldType)) {
                FieldType::TYPE_LOV_ITEM => true,
                FieldType::TYPE_OBJECT => static::linksByCodeInForm($component, $get, $inherited),
                default => false,
            },
            $inherited,
        );

        return [$component->getName() => $component];
    }

    protected static function displayUnitComponents(Closure $fieldType, ?Closure $inherited): array
    {
        $unitClass = fn (Select $component) => TypeSettings::getUnitClass((string)$component->evaluate($fieldType));

        $displayUnit = Select::make(self::DISPLAY_UNIT_STATE_PATH)
            ->label(__('mutable-content-filament::ui.settings.display_unit'))
            ->options(function (Select $component) use ($unitClass) {
                $class = $unitClass($component);

                return $class ? $class::unitOptions() : [];
            })
            ->helperText(function (Select $component) use ($unitClass) {
                $class = $unitClass($component);

                return $class ? __('mutable-content-filament::ui.settings.display_unit_helper', ['unit' => $class::unitLabel($class::baseUnit())]) : null;
            })
            ->visible(fn (Select $component) => $unitClass($component) !== null);

        if ($inherited) {
            return [$displayUnit->getName() => $displayUnit
                ->placeholder(function (Select $component) use ($inherited, $unitClass) {
                    $class = $unitClass($component);

                    if (!$class) {
                        return null;
                    }

                    $unit = $component->evaluate($inherited)[TypeSettings::DISPLAY_UNIT] ?? null;

                    return __('mutable-content-filament::ui.settings.inherited', ['value' => $class::unitLabel(in_array($unit, $class::units(), true) ? $unit : $class::baseUnit())]);
                })
                ->formatStateUsing(fn (?ModelWithFields $record) => $record?->getField(Field::CODE_FIELD_TYPE_SETTINGS)[TypeSettings::DISPLAY_UNIT] ?? null)];
        }

        return [$displayUnit->getName() => $displayUnit
            ->selectablePlaceholder(false)
            ->formatStateUsing(function (Select $component, ?ModelWithFields $record) use ($unitClass) {
                $class = $unitClass($component);

                return $record?->getField(Field::CODE_FIELD_TYPE_SETTINGS)[TypeSettings::DISPLAY_UNIT] ?? ($class ? $class::baseUnit() : null);
            })
            ->dehydrateStateUsing(function (Select $component, $state) use ($unitClass) {
                $class = $unitClass($component);

                return $class && $state !== $class::baseUnit() && in_array($state, $class::units(), true) ? $state : null;
            })];
    }

    /**
     * Short description of the set type settings, for tables.
     *
     * @return array<string>
     */
    public static function describe(string $fieldType, mixed $settings): array
    {
        $result = [];

        $unitClass = TypeSettings::getUnitClass($fieldType);
        $unit = is_array($settings) || $settings instanceof ArrayAccess ? ($settings[TypeSettings::DISPLAY_UNIT] ?? null) : null;

        if ($unitClass && in_array($unit, $unitClass::units(), true)) {
            $result[] = __('mutable-content-filament::ui.settings.describe.display_unit', ['unit' => $unitClass::unitLabel($unit)]);
        }

        if ($fieldType === FieldType::TYPE_OBJECT) {
            $linkByCode = is_array($settings) || $settings instanceof ArrayAccess ? ($settings[TypeSettings::LINK_BY_CODE] ?? null) : null;

            if ($linkByCode === true) {
                $result[] = __('mutable-content-filament::ui.settings.describe.link_by_code');
            } elseif ($linkByCode === false) {
                $result[] = __('mutable-content-filament::ui.settings.describe.link_by_id');
            }
        }

        if (in_array($fieldType, [FieldType::TYPE_LOV_ITEM, FieldType::TYPE_OBJECT], true)) {
            $allowUnlisted = is_array($settings) || $settings instanceof ArrayAccess ? ($settings[TypeSettings::ALLOW_UNLISTED_CODES] ?? null) : null;

            if ($allowUnlisted === true) {
                $result[] = __('mutable-content-filament::ui.settings.describe.unlisted_codes');
            } elseif ($allowUnlisted === false) {
                $result[] = __('mutable-content-filament::ui.settings.describe.listed_codes_only');
            }
        }

        if (in_array($fieldType, TypeSettings::ALLOW_ZERO_TYPES, true)) {
            $allowZero = is_array($settings) || $settings instanceof ArrayAccess ? ($settings[TypeSettings::ALLOW_ZERO] ?? null) : null;

            if ($allowZero === true) {
                $result[] = __('mutable-content-filament::ui.settings.describe.allow_zero');
            } elseif ($allowZero === false) {
                $result[] = __('mutable-content-filament::ui.settings.describe.no_zero');
            }
        }

        return $result;
    }

    public static function defaultDisplayUnit(?string $fieldType): ?string
    {
        $class = TypeSettings::getUnitClass((string)$fieldType);

        return $class ? $class::baseUnit() : null;
    }
}
