<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Lovs;

use BackedEnum;
use UnitEnum;

use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Table;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Lov as LovModel;

use Amarenkov\MutableContentFilament\Filament\Actions\DeleteAction;
use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;

use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Pages\ManageLovs;

class LovResource extends Resource
{
    protected static ?string $model = LovModel::class;
    protected static ?string $slug = 'lovs';

    protected static ?string $recordRouteKeyName = 'fields->'.Field::COMMON_CODE_CODE;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('mutable-content-filament::ui.navigation_group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mutable-content-filament::ui.lovs');
    }

    protected static function formComponentsFromFields(?array $scopes = null)
    {
        $components = parent::formComponentsFromFields($scopes);

        $components[Field::COMMON_CODE_CODE]
            ->disabled(fn (?LovModel $record) => $record && ($record->isSystem() || $record->isUsedInFields()))
            ->helperText(fn (?LovModel $record) => $record && !$record->isSystem() && $record->isUsedInFields()
                ? $record->usedInFieldsMessage(key: 'code_used_in_fields')
                : null);

        return $components;
    }

    public static function recordActions()
    {
        return array_map(function ($action) {
            if ($action instanceof DeleteAction) {
                $action->before(function (DeleteAction $action, LovModel $record) {
                    if ($record->isUsedInFields()) {
                        Notification::make()
                            ->danger()
                            ->title(__('mutable-content-filament::ui.lov_delete_denied'))
                            ->body($record->usedInFieldsMessage())
                            ->send();

                        $action->cancel();
                    }
                });
            }

            return $action;
        }, parent::recordActions());
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->defaultSort(Field::COMMON_CODE_LABEL)
            ->recordUrl(function (LovModel $lov): string {
                return parent::getUrl('items.index', ['lov' => $lov->code()]);
            });
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ManageLovs::route('/'),
        ];
    }
}
