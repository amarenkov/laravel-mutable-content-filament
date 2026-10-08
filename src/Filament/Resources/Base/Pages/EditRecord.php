<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages;

use Illuminate\Database\Eloquent\Model;

use Filament\Resources\Pages\EditRecord as BaseEditRecord;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;
use Amarenkov\MutableContentFilament\Helpers\SaveHelper;

class EditRecord extends BaseEditRecord
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (@$data['fields']) {
            $data = $data['fields'];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->setUpdatedByIfDirty(RequestHelper::getPath(), auth()->id());

        SaveHelper::save(fn () => $record->save());

        return $record;
    }
}