<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages;

use Illuminate\Contracts\Support\Htmlable;

use Amarenkov\MutableContentFilament\Filament\Actions\Lovs\CreateItemsAction;
use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\ManageRecords;

use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\ItemResource;

use Amarenkov\MutableContent\Models\Lov\Lov;

class ManageItems extends ManageRecords
{
    protected static string $resource = ItemResource::class;


    public function getTitle(): string|Htmlable
    {
        $lov = $this->getParentRecord();

        return __('mutable-content-filament::ui.lov_items_title', ['lov' => $lov->label() !== '' ? $lov->label() : $lov->code()]);
    }

    protected function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions(), [
            CreateItemsAction::make()
                ->lov(fn (): Lov => $this->getParentRecord()),
        ]);
    }

    public function getResourceBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $resource = static::getResource();
            
        $parentResourceRegistration = $resource::getParentResourceRegistration();
        $parentResource = $parentResourceRegistration?->getParentResource();
        $parentRecord = $this->getParentRecord();

        $parentRecordTitle = $parentResource::hasRecordTitle() ?
            $parentResource::getRecordTitle($parentRecord) :
            $parentResource::getTitleCaseModelLabel();
        
        $breadcrumbs[$parentResource::getUrl()] = $parentResource::getBreadcrumb();
        $breadcrumbs[] = $parentRecordTitle;

        return $breadcrumbs;
    }
}