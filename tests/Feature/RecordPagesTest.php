<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Actions\Testing\TestAction;

use Illuminate\Support\Facades\DB;

use Livewire\Livewire;

use Amarenkov\MutableContent\Helpers\LogHelper;

use Amarenkov\MutableContentFilament\Tests\TestCase;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Owner;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Record;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\CreateRecordPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\EditRecordPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\ListRecordsPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\ViewRecordPage;

class RecordPagesTest extends TestCase
{
    protected function makeOwner(string $code, string $label): Owner
    {
        $owner = new Owner();
        $owner->fill(['code' => $code, 'label' => $label]);
        $owner->save();

        return $owner;
    }

    protected function makeRecord(array $fields): Record
    {
        $record = new Record();
        $record->fill($fields);
        $record->save();

        return $record;
    }

    public function test_create_saves_fields_and_logs_the_author(): void
    {
        $owner = $this->makeOwner('OWN-1', 'Owner One');

        Livewire::test(CreateRecordPage::class)
            ->fillForm([
                'code' => 'REC-1',
                'quantity' => 3,
                'weight' => 2.5,
                'is_active' => true,
                'due_date' => '2026-10-08',
                'status' => 'draft',
                'owner_id' => $owner->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $record = Record::where('code', 'REC-1')->first();

        $this->assertNotNull($record);
        $this->assertEqualsCanonicalizing(
            ['code' => 'REC-1', 'quantity' => 3, 'weight' => 2.5, 'is_active' => true, 'due_date' => '2026-10-08', 'status' => 'draft', 'owner_id' => $owner->id],
            $record->fields->getArrayCopy()
        );
        $this->assertSame($this->user->id, DB::table(LogHelper::getLogsTable(Record::class))->where('entity_id', $record->id)->value('user_id'));
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateRecordPage::class)
            ->fillForm(['code' => 'REC-2'])
            ->call('create')
            ->assertHasFormErrors(['quantity' => 'required']);

        $this->assertSame(0, Record::count());
    }

    public function test_duplicate_is_reported_as_notification(): void
    {
        $this->makeRecord(['code' => 'REC-3', 'quantity' => 1]);

        Livewire::test(CreateRecordPage::class)
            ->fillForm(['code' => 'REC-3', 'quantity' => 2])
            ->call('create')
            ->assertNotified('Such a record already exists');

        $this->assertSame(1, Record::count());
    }

    public function test_edit_fills_and_saves_fields(): void
    {
        $record = $this->makeRecord(['code' => 'REC-4', 'quantity' => 1, 'weight' => 1.5, 'status' => 'draft']);

        Livewire::test(EditRecordPage::class, ['record' => $record->getRouteKey()])
            ->assertFormSet(['code' => 'REC-4', 'quantity' => 1, 'status' => 'draft'])
            ->fillForm(['quantity' => 5, 'status' => 'active'])
            ->call('save')
            ->assertHasNoFormErrors();

        $record->refresh();

        $this->assertSame(5, $record->quantity);
        $this->assertSame('active', $record->status);
        $this->assertSame(1.5, $record->weight);
    }

    public function test_system_record_locks_fields_and_hides_delete(): void
    {
        $record = $this->makeRecord(['code' => 'REC-5', 'quantity' => 1, 'is_system' => true]);

        Livewire::test(EditRecordPage::class, ['record' => $record->getRouteKey()])
            ->assertFormFieldDisabled('quantity');

        Livewire::test(ListRecordsPage::class)
            ->assertActionHidden(TestAction::make('delete')->table($record));
    }

    public function test_table_and_view_show_titles_instead_of_codes(): void
    {
        $owner = $this->makeOwner('OWN-2', 'Owner Two');
        $record = $this->makeRecord(['code' => 'REC-6', 'quantity' => 1, 'weight' => 1.25, 'status' => 'active', 'owner_id' => $owner->id]);

        Livewire::test(ListRecordsPage::class)
            ->assertCanSeeTableRecords([$record])
            ->assertTableColumnFormattedStateSet('owner_id', 'Owner Two', $record);

        $this->get(ViewRecordPage::getUrl(['record' => $record]))
            ->assertOk()
            ->assertSee('Owner Two')
            ->assertSee('Active')
            ->assertSee('1,25 kg');
    }

    public function test_delete_soft_deletes_and_logs_the_author(): void
    {
        $record = $this->makeRecord(['code' => 'REC-7', 'quantity' => 1]);

        Livewire::test(ListRecordsPage::class)
            ->callAction(TestAction::make('delete')->table($record));

        $this->assertSoftDeleted('records', ['id' => $record->id]);
        $this->assertSame($this->user->id, DB::table(LogHelper::getLogsTable(Record::class))->where('entity_id', $record->id)->where('is_deleted', true)->value('user_id'));
    }
}
