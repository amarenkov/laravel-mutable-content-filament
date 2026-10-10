<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Icons\Heroicon;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModels;
use Amarenkov\MutableContent\Models\ModelWithFields;

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
            ->required()
            ->disabled(fn (?FieldUsageModels $record) => $record && $record->isSystem())
            ->afterStateUpdated(fn (Set $set) => $set(FieldUsageModels::CODE_TYPE_CODE, null))
            ->rule(function ($livewire) {
                return new FieldAllowedInClass($livewire->getParentRecord()->code());
            });

        $typeComponent = $components[FieldUsageModels::CODE_TYPE_CODE];

        $components[FieldUsageModels::CODE_TYPE_CODE] = Select::make(FieldUsageModels::CODE_TYPE_CODE)
            ->label($typeComponent->getLabel())
            ->options(fn (Get $get) => static::typeOptionsOf($get(FieldUsageModels::CODE_MUTABLE_CLASS)))
            ->placeholder(__('mutable-content-filament::ui.usage_all_types'))
            ->helperText(__('mutable-content-filament::ui.usage_type_helper'))
            ->visible(fn (Get $get) => static::typeOptionsOf($get(FieldUsageModels::CODE_MUTABLE_CLASS)) !== [])
            ->dehydratedWhenHidden()
            ->dehydrateStateUsing(fn ($state, Get $get) => filled($state) && static::typeOptionsOf($get(FieldUsageModels::CODE_MUTABLE_CLASS)) !== [] ? $state : null)
            ->disabled(fn (?FieldUsageModels $record) => $record && $record->isSystem());

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
        $mutableClass = FieldUsageModels::getScopeMutableClass($scope);

        if ($mutableClass === null) {
            return $scope;
        }

        $title = app(MutableClassRegistry::class)->getKeyValuePairs()[$mutableClass] ?? $mutableClass;
        $typeCode = FieldUsageModels::getScopeTypeCode($scope);

        if ($typeCode === null) {
            return $title;
        }

        return $title.': '.(static::typeOptionsOf($mutableClass)[$typeCode] ?? $typeCode);
    }

    /**
     * Scopes in display order with their titles: each class by label followed by its types.
     *
     * @return array<string, string>
     */
    public static function orderedScopes(): array
    {
        $mcds = app(MutableClassRegistry::class)->all();
        usort($mcds, fn ($a, $b) => mb_strtolower($a->label) <=> mb_strtolower($b->label));

        $result = [];

        foreach ($mcds as $mcd) {
            $mutableClass = $mcd->mutableClass;

            $result[$mutableClass::getClassScope()] = $mcd->label;

            $types = $mutableClass::getTypeOptions();
            uasort($types, fn ($a, $b) => mb_strtolower($a) <=> mb_strtolower($b));

            foreach ($types as $typeCode => $typeLabel) {
                $result[$mutableClass::getTypeScope((string)$typeCode)] = $mcd->label.': '.$typeLabel;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected static function typeOptionsOf(mixed $mutableClass): array
    {
        return is_string($mutableClass) && is_subclass_of($mutableClass, ModelWithFields::class) ? $mutableClass::getTypeOptions() : [];
    }

    protected static function tableFromFields(Table $table, ?array $scopes = null)
    {
        $table = parent::tableFromFields($table, $scopes);

        $fields = static::$model::getFieldDefinitions($scopes);
        $replacedCodes = [FieldUsageModels::CODE_MUTABLE_CLASS, FieldUsageModels::CODE_TYPE_CODE, ...array_keys(static::usageMarks())];

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

        return $table
            ->columns($columns)
            ->filters([
                static::fieldsSelectFilter(FieldUsageModels::CODE_MUTABLE_CLASS, $fields[FieldUsageModels::CODE_MUTABLE_CLASS]->label, fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
                SelectFilter::make(FieldUsageModels::CODE_TYPE_CODE)
                    ->label($fields[FieldUsageModels::CODE_TYPE_CODE]->label)
                    ->options(fn () => array_filter(static::orderedScopes(), fn ($scope) => FieldUsageModels::getScopeTypeCode($scope) !== null, ARRAY_FILTER_USE_KEY))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->where(FieldUsageModels::CODE_SCOPE, $data['value']) : $query),
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
                $text = $fields[FieldUsageModels::CODE_MUTABLE_CLASS]->label.': '.static::getScopeTitle((string)$record->{FieldUsageModels::CODE_SCOPE});

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
        $scopes = array_keys(static::orderedScopes());

        return parent::table($table)
            ->recordTitle(fn (FieldUsageModels $record) => static::getRecordTitle($record))
            ->defaultSort(function (Builder $query, string $direction) use ($scopes) {
                if (!$scopes) {
                    return $query;
                }

                $cases = implode(' ', array_map(fn ($i) => 'WHEN ? THEN '.$i, array_keys($scopes)));

                $last = $direction === 'desc' ? -1 : count($scopes);

                return $query->orderByRaw(
                    'CASE '.FieldUsageModels::CODE_SCOPE.' '.$cases.' ELSE '.$last.' END '.($direction === 'desc' ? 'desc' : 'asc'),
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