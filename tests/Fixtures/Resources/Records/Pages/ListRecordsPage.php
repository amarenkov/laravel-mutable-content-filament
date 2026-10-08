<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\ListRecords;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\RecordResource;

class ListRecordsPage extends ListRecords
{
    protected static string $resource = RecordResource::class;
}
