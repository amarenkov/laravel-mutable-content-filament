<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages;

use Illuminate\Database\Eloquent\Model;

use Filament\Actions\CreateAction;

use Filament\Resources\Pages\ManageRecords as BaseManageRecords;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;
use Amarenkov\MutableContentFilament\Helpers\SaveHelper;

class ManageRecords extends BaseManageRecords
{
    public function hasResourceBreadcrumbs(): bool
    {
        return true;
    }

    protected function makeRecord(string $model): Model
    {
        if ($parentRecord = $this->getParentRecord()) {
            return static::getResource()::getParentResourceRegistration()->getRelationship($parentRecord)->make();
        }

        return new $model();
    }

    protected function associateRecordWithParent(Model $record, Model $parent): Model
    {
        return static::getResource()::getParentResourceRegistration()->getRelationship($parent)->save($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    $record = $this->makeRecord($model);

                    $record->fill($data);
                    $record->withLogContext(RequestHelper::getPath().'/create')->user(auth()->id());

                    return SaveHelper::save(function () use ($record) {
                        if ($parentRecord = $this->getParentRecord()) {
                            return $this->associateRecordWithParent($record, $parentRecord);
                        }

                        $record->save();

                        return $record;
                    });
                }),
        ];
    }
}