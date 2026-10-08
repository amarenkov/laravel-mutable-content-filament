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
                $record->setUpdatedBy(RequestHelper::getPath().'/destroy', auth()->id());

                SaveHelper::save(fn () => $record->delete());

                return $record;
            });
    }
}