<?php

namespace Amarenkov\MutableContentFilament\Filament\Actions;

use Illuminate\Database\Eloquent\Model;

use Filament\Actions\DeleteAction as BaseDeleteAction;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;
use Amarenkov\MutableContentFilament\Helpers\SaveHelper;

class DeleteAction extends BaseDeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hidden(function($record) {
                return $record && $record->isSystem();
            })
            ->using(function (Model $record): Model {
                SaveHelper::prepareLog($record, RequestHelper::getPath().'/destroy');

                SaveHelper::save(fn () => $record->delete());

                return $record;
            });
    }
}