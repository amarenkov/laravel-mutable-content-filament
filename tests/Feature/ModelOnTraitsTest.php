<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Actions\Testing\TestAction;

use Livewire\Livewire;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Note;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\NoteResource;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\CreateNotePage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\EditNotePage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Notes\Pages\ListNotesPage;
use Amarenkov\MutableContentFilament\Tests\TestCase;

class ModelOnTraitsTest extends TestCase
{
    public function test_model_without_change_log_and_soft_deletes_is_managed(): void
    {
        $this->assertSame('Note', NoteResource::getModelLabel());

        Livewire::test(CreateNotePage::class)
            ->fillForm(['body' => 'First'])
            ->call('create')
            ->assertHasNoFormErrors();

        $note = Note::query()->first();

        $this->assertSame('First', $note->body);

        Livewire::test(EditNotePage::class, ['record' => $note->getRouteKey()])
            ->assertFormSet(['body' => 'First'])
            ->fillForm(['body' => 'Second'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Second', $note->refresh()->body);

        Livewire::test(ListNotesPage::class)
            ->assertSeeText('Second')
            ->callAction(TestAction::make('delete')->table($note));

        $this->assertSame(0, Note::query()->count());
    }
}
