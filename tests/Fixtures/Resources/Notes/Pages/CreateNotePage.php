<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\CreateRecord;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\NoteResource;

class CreateNotePage extends CreateRecord
{
    protected static string $resource = NoteResource::class;
}
