<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Actions\Testing\TestAction;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Pages\ManageFields;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\Pages\ManageUsage;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Pages\ManageLovs;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;

use Amarenkov\MutableContentFilament\Tests\TestCase;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Record;

class ManagementTest extends TestCase
{
    public function test_field_is_created_and_bound_to_a_class(): void
    {
        Livewire::test(ManageFields::class)
            ->callAction('create', ['code' => 'note', 'label' => 'Note', 'field_type' => Type::TYPE_STRING])
            ->assertHasNoActionErrors();

        $field = Field::where('code', 'note')->first();

        $this->assertNotNull($field);
        $this->assertFalse($field->isSystem());

        Livewire::test(ManageUsage::class, ['parentRecord' => $field])
            ->callAction('create', [Usage::CODE_MUTABLE_CLASS => Record::class, Usage::CODE_IS_REQUIRED => true])
            ->assertHasNoActionErrors();

        $this->assertSame([Usage::makeScope(Usage::CODE_MUTABLE_CLASS, Record::class)], $field->usages()->pluck('scope')->all());
        $this->assertTrue(Record::getFieldDefinitions()['note']->usage()->isRequired);
    }

    public function test_invalid_field_code_is_rejected(): void
    {
        Livewire::test(ManageFields::class)
            ->callAction('create', ['code' => 'Bad Code', 'label' => 'Bad', 'field_type' => Type::TYPE_STRING])
            ->assertHasActionErrors(['code']);

        $this->assertNull(Field::where('label', 'Bad')->first());
    }

    public function test_system_field_cannot_be_deleted(): void
    {
        Livewire::test(ManageFields::class)
            ->assertActionHidden(TestAction::make('delete')->table(Field::where('code', 'quantity')->first()));
    }

    public function test_items_are_added_as_a_list(): void
    {
        $lov = new Lov();
        $lov->fill(['code' => 'colors', 'label' => 'Colors']);
        $lov->save();

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('createItems', ['labels' => "Red\nGreen\nred"])
            ->assertNotified('Items added');

        $this->assertSame(['green', 'red'], $lov->items()->orderBy('code')->pluck('code')->all());
    }

    public function test_used_lov_cannot_be_deleted(): void
    {
        $lov = new Lov();
        $lov->fill(['code' => 'sizes', 'label' => 'Sizes']);
        $lov->save();

        $field = new Field();
        $field->fill(['code' => 'size', 'label' => 'Size', 'field_type' => Type::TYPE_LOV_ITEM, 'lov_code' => 'sizes']);
        $field->save();

        Livewire::test(ManageLovs::class)
            ->callAction(TestAction::make('delete')->table($lov))
            ->assertNotified('The LOV cannot be deleted');

        $this->assertNotSoftDeleted('lovs', ['id' => $lov->id]);
    }
}
