<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\Pages;

use Illuminate\Contracts\Support\Htmlable;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\ManageRecords;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\UsageResource;

class ManageUsage extends ManageRecords
{
    protected static string $resource = UsageResource::class;


    public function getTitle(): string|Htmlable
    {
        $field = $this->getParentRecord();

        return __('mutable-content-filament::ui.usage_title', ['field' => $field->label() !== '' ? $field->label() : $field->code()]);
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