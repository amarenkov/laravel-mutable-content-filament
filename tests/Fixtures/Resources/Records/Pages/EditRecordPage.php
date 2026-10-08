<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\EditRecord;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\RecordResource;

class EditRecordPage extends EditRecord
{
    protected static string $resource = RecordResource::class;
}
