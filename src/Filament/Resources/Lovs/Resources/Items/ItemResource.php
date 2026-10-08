<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items;

use Filament\Tables\Table;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Item as LovItemModels;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\LovResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;

class ItemResource extends Resource
{
    protected static ?string $model = LovItemModels::class;

    protected static ?string $parentResource = LovResource::class;

    public static function getPluralModelLabel(): string
    {
        return __('mutable-content-filament::ui.lov_items');
    }

    protected static function getFieldScopes(mixed $livewire): ?array
    {
        $lov = $livewire && method_exists($livewire, 'getParentRecord') ? $livewire->getParentRecord() : null;

        return $lov ? LovItemModels::getFieldScopesForLov($lov->code()) : null;
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->defaultSort(Field::COMMON_CODE_LABEL);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageItems::route('/'),
        ];
    }
}