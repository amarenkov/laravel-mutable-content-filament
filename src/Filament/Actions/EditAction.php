<?php

namespace Amarenkov\MutableContentFilament\Filament\Actions;

use Illuminate\Database\Eloquent\Model;

use Filament\Actions\EditAction as BaseEditAction;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;
use Amarenkov\MutableContentFilament\Helpers\SaveHelper;

class EditAction extends BaseEditAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->mutateRecordDataUsing(function (array $data): array {
                if (@$data['fields']) {
                    $data = $data['fields'];
                }

                return $data;
            })
            ->using(function (Model $record, array $data): Model {
                $record->fill($data);
                $record->withLogContext(RequestHelper::getPath().'/edit')->user(auth()->id());
                SaveHelper::save(fn () => $record->save());

                return $record;
            });
    }
}