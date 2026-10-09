<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages;

use Illuminate\Database\Eloquent\Model;

use Filament\Actions\CreateAction;

use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;
use Amarenkov\MutableContentFilament\Helpers\SaveHelper;

class CreateRecord extends BaseCreateRecord
{
    protected function handleRecordCreation(array $data): Model
    {
        $parentRecord = $this->getParentRecord();

        $record = $parentRecord
            ? static::getResource()::getParentResourceRegistration()->getRelationship($parentRecord)->make()
            : new ($this->getModel())();

        $record->fill($data);
        $record->withLogContext(RequestHelper::getPath())->user(auth()->id());

        return SaveHelper::save(function () use ($record, $parentRecord) {
            if ($parentRecord) {
                return $this->associateRecordWithParent($record, $parentRecord);
            }

            $record->save();

            return $record;
        });
    }
}