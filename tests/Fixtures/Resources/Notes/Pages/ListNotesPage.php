<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\ListRecords;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\NoteResource;

class ListNotesPage extends ListRecords
{
    protected static string $resource = NoteResource::class;
}
