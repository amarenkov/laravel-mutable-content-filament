<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields;

use BackedEnum;
use UnitEnum;

use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\generate_icon_html;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as FieldType;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContent\Helpers\ObjectHelper;

use Amarenkov\MutableContent\Rules\FieldCode as FieldCodeRule;

use Amarenkov\MutableContent\Models\Field\Field as FieldModel;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModel;

use Amarenkov\MutableContentFilament\Helpers\IconHelper;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Pages\ManageFields;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class FieldResource extends Resource
{
    protected static ?string $model = FieldModel::class;
    protected static ?string $slug = 'fields';

    protected static ?string $recordRouteKeyName = 'fields->'.Field::COMMON_CODE_CODE;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleGroup;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('mutable-content-filament::ui.navigation_group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mutable-content-filament::ui.fields');
    }

    protected static function formComponentsFromFields(?array $scopes = null)
    {
        $components = parent::formComponentsFromFields($scopes);

        $components[Field::COMMON_CODE_CODE]->rule(function (?FieldModel $record) {
            return new FieldCodeRule(
                $record ? $record->usages()->pluck(FieldUsageModel::CODE_SCOPE)->map(fn ($scope) => FieldUsageModel::getScopeMutableClass($scope))->filter()->unique()->values()->all() : []
            );
        });

        $fieldTypeComponent = $components[Field::CODE_FIELD_TYPE];
        $fieldTypeComponent
            ->live()
            ->afterStateUpdated(function (Set $set, ?string $state) {
                $set(FieldTypeSettingsComponents::DISPLAY_UNIT_STATE_PATH, FieldTypeSettingsComponents::defaultDisplayUnit($state));
            });

        $fieldTypeOptions = $fieldTypeComponent->getOptions();
        $fieldTypeComponent->options(function (?FieldModel $record) use ($fieldTypeOptions) {
            if ($record && $record->{Field::CODE_FIELD_TYPE} === FieldType::TYPE_SYSTEM) {
                return $fieldTypeOptions;
            }

            return array_diff_key($fieldTypeOptions, [FieldType::TYPE_SYSTEM => true]);
        });

        $lovCodeComponent = $components[Field::CODE_LOV_CODE];
        $lovCodeComponent->hidden(function(Get $get) use ($fieldTypeComponent) {
            return $get(Field::CODE_FIELD_TYPE) !== FieldType::TYPE_LOV_ITEM;
        });

        $components[Field::CODE_OBJECT_CLASS]->hidden(function(Get $get) {
            return $get(Field::CODE_FIELD_TYPE) !== FieldType::TYPE_OBJECT;
        });

        $components += FieldTypeSettingsComponents::make(fn (Get $get) => $get(Field::CODE_FIELD_TYPE));

        return $components;
    }

    protected static function tableFromFields(Table $table, ?array $scopes = null)
    {
        $table = parent::tableFromFields($table, $scopes);

        $detailCodes = [Field::CODE_FIELD_TYPE, Field::CODE_LOV_CODE, Field::CODE_OBJECT_CLASS];

        $columns = [];

        foreach ($table->getColumns() as $name => $column) {
            if ($name === Field::CODE_FIELD_TYPE) {
                $columns[] = static::detailsColumn();
            }

            if (!in_array($name, $detailCodes, true)) {
                $columns[] = $column;
            }
        }

        $lovRegistry = app(LovRegistry::class);

        return $table
            ->columns($columns)
            ->filters([
                static::fieldsSelectFilter(Field::CODE_FIELD_TYPE, __('Field type'), fn () => $lovRegistry->getLovItemsOptions(FieldType::CLASS_CODE)),
                static::fieldsSelectFilter(Field::CODE_LOV_CODE, __('LOV'), fn () => $lovRegistry->getLovsOptions() ?? []),
                static::fieldsSelectFilter(Field::CODE_OBJECT_CLASS, __('Object class'), fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
                ...array_values($table->getFilters(withHidden: true)),
            ]);
    }

    protected static function detailsColumn(): TextColumn
    {
        $lovRegistry = app(LovRegistry::class);

        return TextColumn::make('details')
            ->label(__('mutable-content-filament::ui.type'))
            ->state(function (FieldModel $record) use ($lovRegistry) {
                $fieldType = (string)$record->{Field::CODE_FIELD_TYPE};

                $result = $lovRegistry->getLovItemLabel(FieldType::CLASS_CODE, $fieldType) ?? $fieldType;

                $detail = match ($fieldType) {
                    FieldType::TYPE_LOV_ITEM => filled($record->{Field::CODE_LOV_CODE}) ? ($lovRegistry->getLovLabel($record->{Field::CODE_LOV_CODE}) ?? $record->{Field::CODE_LOV_CODE}) : null,
                    FieldType::TYPE_OBJECT => filled($record->{Field::CODE_OBJECT_CLASS}) ? ObjectHelper::getClassLabel($record->{Field::CODE_OBJECT_CLASS}) : null,
                    default => null,
                };

                if ($detail !== null) {
                    $result .= ': '.$detail;
                }

                if ($settings = FieldTypeSettingsComponents::describe($fieldType, $record->{Field::CODE_FIELD_TYPE_SETTINGS})) {
                    $result .= ' ('.implode(', ', $settings).')';
                }

                $html = e($result);

                if ($icon = IconHelper::lovItemIcon(FieldType::CLASS_CODE, $fieldType)) {
                    $html = '<span style="display: inline-flex; align-items: center; gap: 0.375rem;">'.generate_icon_html($icon, size: IconSize::Small)?->toHtml().$html.'</span>';
                }

                return new HtmlString($html);
            });
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->defaultSort(Field::COMMON_CODE_LABEL)
            ->recordUrl(function (FieldModel $field): string {
                return parent::getUrl('usages.index', ['field' => $field->code()]);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFields::route('/'),
        ];
    }
}
