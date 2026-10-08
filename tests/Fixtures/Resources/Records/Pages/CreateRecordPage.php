<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\CreateRecord;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\RecordResource;

class CreateRecordPage extends CreateRecord
{
    protected static string $resource = RecordResource::class;
}
