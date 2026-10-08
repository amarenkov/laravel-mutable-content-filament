<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage;

use Closure;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Icons\Heroicon;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;
use Amarenkov\MutableContent\Helpers\FieldCodeHelper;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModels;

use Amarenkov\MutableContent\Rules\FieldAllowedInClass;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldTypeSettingsComponents;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\Pages\ManageUsage;

class UsageResource extends Resource
{
    protected static ?string $model = FieldUsageModels::class;
    protected static ?string $slug = 'usage';

    protected static ?string $parentResource = FieldResource::class;

    public static function getPluralModelLabel(): string
    {
        return __('mutable-content-filament::ui.usage');
    }

    protected static ?string $recordTitleAttribute = FieldUsageModels::CODE_SCOPE;

    protected static function formComponentsFromFields(?array $scopes = null)
    {
        $components = parent::formComponentsFromFields($scopes);

        $components[FieldUsageModels::CODE_MUTABLE_CLASS]
            ->live()
            ->requiredWithout(FieldUsageModels::CODE_LOV_CODE)
            ->prohibits(FieldUsageModels::CODE_LOV_CODE)
            ->disabled(fn (Get $get, ?FieldUsageModels $record) => filled($get(FieldUsageModels::CODE_LOV_CODE)) || ($record && $record->isSystem()))
            ->rule(function ($livewire) {
                return new FieldAllowedInClass($livewire->getParentRecord()->code());
            });

        $components[FieldUsageModels::CODE_LOV_CODE]
            ->live()
            ->requiredWithout(FieldUsageModels::CODE_MUTABLE_CLASS)
            ->prohibits(FieldUsageModels::CODE_MUTABLE_CLASS)
            ->disabled(fn (Get $get, ?FieldUsageModels $record) => filled($get(FieldUsageModels::CODE_MUTABLE_CLASS)) || ($record && $record->isSystem()))
            ->helperText(__('mutable-content-filament::ui.usage_lov_helper'))
            ->rule(function ($livewire) {
                $fieldCode = $livewire->getParentRecord()->code();

                return function (string $attribute, mixed $value, Closure $fail) use ($fieldCode) {
                    if ($error = FieldCodeHelper::getErrorForClass($fieldCode, FieldUsageModels::LOV_MUTABLE_CLASS)) {
                        $fail($error);
                    }
                };
            });

        $components += FieldTypeSettingsComponents::make(
            fn ($livewire) => $livewire->getParentRecord()->{Field::CODE_FIELD_TYPE},
            fn ($livewire) => $livewire->getParentRecord()->getField(Field::CODE_FIELD_TYPE_SETTINGS) ?? []
        );

        return $components;
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (!$record instanceof FieldUsageModels) {
            return parent::getRecordTitle($record);
        }

        return static::getScopeTitle((string)$record->{FieldUsageModels::CODE_SCOPE});
    }

    public static function getScopeTitle(string $scope): string
    {
        [$type, $value] = FieldUsageModels::parseScope($scope);

        return match ($type) {
            FieldUsageModels::CODE_MUTABLE_CLASS => app(MutableClassRegistry::class)->getKeyValuePairs()[$value] ?? $value,
            FieldUsageModels::CODE_LOV_CODE => __('LOV').': '.(app(LovRegistry::class)->getLovLabel($value) ?? $value),
            default => $scope,
        };
    }

    protected static function tableFromFields(Table $table, ?array $scopes = null)
    {
        $table = parent::tableFromFields($table, $scopes);

        $fields = static::$model::getFieldDefinitions($scopes);
        $replacedCodes = [FieldUsageModels::CODE_MUTABLE_CLASS, FieldUsageModels::CODE_LOV_CODE, ...array_keys(static::usageMarks())];

        $columns = [];

        foreach ($table->getColumns() as $name => $column) {
            if ($name === FieldUsageModels::CODE_MUTABLE_CLASS) {
                $columns[] = static::usageColumn($fields);
                $columns[] = static::typeSettingsColumn($fields);
            }

            if (!in_array($name, $replacedCodes, true)) {
                $columns[] = $column;
            }
        }

        $lovRegistry = app(LovRegistry::class);

        return $table
            ->columns($columns)
            ->filters([
                static::fieldsSelectFilter(FieldUsageModels::CODE_MUTABLE_CLASS, $fields[FieldUsageModels::CODE_MUTABLE_CLASS]->label, fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
                static::fieldsSelectFilter(FieldUsageModels::CODE_LOV_CODE, $fields[FieldUsageModels::CODE_LOV_CODE]->label, fn () => $lovRegistry->getLovsOptions() ?? []),
                ...array_values($table->getFilters(withHidden: true)),
                ...array_map(fn ($code) => static::boolFilter($fields[$code]), array_values(array_diff(array_keys(static::usageMarks()), [Field::COMMON_CODE_IS_SYSTEM]))),
            ]);
    }

    /**
     * Boolean usage fields shown as icons in the usage column.
     *
     * @return array<string, Heroicon>
     */
    protected static function usageMarks(): array
    {
        return [
            Field::COMMON_CODE_IS_SYSTEM => Heroicon::OutlinedLockClosed,
            FieldUsageModels::CODE_IS_REQUIRED => Heroicon::OutlinedExclamationCircle,
            FieldUsageModels::CODE_IS_IMMUTABLE => Heroicon::OutlinedNoSymbol,
            FieldUsageModels::CODE_IS_IMMUTABLE_FOR_SYSTEM_OBJECTS => Heroicon::OutlinedShieldExclamation,
        ];
    }

    protected static function usageColumn(array $fields): TextColumn
    {
        return TextColumn::make('usage')
            ->label(__('mutable-content-filament::ui.usage'))
            ->state(function (FieldUsageModels $record) use ($fields) {
                [$type, $value] = FieldUsageModels::parseScope((string)$record->{FieldUsageModels::CODE_SCOPE});

                $text = match ($type) {
                    FieldUsageModels::CODE_MUTABLE_CLASS => $fields[$type]->label.': '.(app(MutableClassRegistry::class)->getKeyValuePairs()[$value] ?? $value),
                    FieldUsageModels::CODE_LOV_CODE => $fields[$type]->label.': '.(app(LovRegistry::class)->getLovLabel($value) ?? $value),
                    default => (string)$record->{FieldUsageModels::CODE_SCOPE},
                };

                $marks = [];

                foreach (static::usageMarks() as $code => $icon) {
                    if ($record->{$code} && isset($fields[$code])) {
                        $marks[] = [$icon, $fields[$code]->label];
                    }
                }

                return static::textWithMarks($text, $marks);
            });
    }

    protected static function typeSettingsColumn(array $fields): TextColumn
    {
        return TextColumn::make(FieldUsageModels::CODE_FIELD_TYPE_SETTINGS)
            ->label($fields[FieldUsageModels::CODE_FIELD_TYPE_SETTINGS]->label ?? __('Type settings'))
            ->state(function (FieldUsageModels $record, $livewire) {
                $settings = FieldTypeSettingsComponents::describe(
                    (string)$livewire->getParentRecord()->{Field::CODE_FIELD_TYPE},
                    $record->{FieldUsageModels::CODE_FIELD_TYPE_SETTINGS}
                );

                return $settings ? implode(', ', $settings) : null;
            });
    }

    public static function table(Table $table): Table
    {
        $classLabels = app(MutableClassRegistry::class)->getKeyValuePairs();
        uasort($classLabels, fn ($a, $b) => mb_strtolower($a) <=> mb_strtolower($b));

        $lovLabels = app(LovRegistry::class)->getLovsOptions() ?? [];
        uasort($lovLabels, fn ($a, $b) => mb_strtolower((string)$a) <=> mb_strtolower((string)$b));

        $scopes = array_merge(
            array_map(fn ($class) => $class::getClassScope(), array_keys($classLabels)),
            array_map(fn ($lovCode) => FieldUsageModels::makeScope(FieldUsageModels::CODE_LOV_CODE, (string)$lovCode), array_keys($lovLabels))
        );

        return parent::table($table)
            ->recordTitle(fn (FieldUsageModels $record) => static::getRecordTitle($record))
            ->defaultSort(function (Builder $query, string $direction) use ($scopes) {
                if (!$scopes) {
                    return $query;
                }

                $cases = implode(' ', array_map(fn ($i) => 'WHEN ? THEN '.$i, array_keys($scopes)));

                return $query->orderByRaw(
                    'CASE '.FieldUsageModels::CODE_SCOPE.' '.$cases.' END '.($direction === 'desc' ? 'desc' : 'asc').' nulls last',
                    $scopes
                );
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsage::route('/'),
        ];
    }
}