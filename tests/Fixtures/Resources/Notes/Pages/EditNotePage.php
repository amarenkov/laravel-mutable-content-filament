<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\EditRecord;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\NoteResource;

class EditNotePage extends EditRecord
{
    protected static string $resource = NoteResource::class;
}
