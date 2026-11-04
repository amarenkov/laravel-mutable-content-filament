<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Note;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\CreateNotePage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\EditNotePage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\ListNotesPage;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $slug = 'notes';

    public static function getPages(): array
    {
        return [
            'index' => ListNotesPage::route('/'),
            'create' => CreateNotePage::route('/create'),
            'edit' => EditNotePage::route('/{record}/edit'),
        ];
    }
}
