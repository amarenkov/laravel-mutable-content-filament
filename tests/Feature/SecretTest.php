<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Actions\Testing\TestAction;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldTypeSettingsComponents;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;

use Amarenkov\MutableContentFilament\Tests\TestCase;

class SecretTest extends TestCase
{
    protected function makeLov(): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'service', 'label' => 'Service']);
        $lov->save();

        $field = new Field();
        $field->fill(['code' => 'token', 'label' => 'Token', 'field_type' => Type::TYPE_SECRET]);
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'service']);
        $usage->save();

        return $lov;
    }

    public function test_value_is_encrypted_masked_and_kept_by_an_empty_input(): void
    {
        $lov = $this->makeLov();

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'mail', 'label' => 'Mail', 'token' => 'sk-123456789'])
            ->assertHasNoActionErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertNotSame('sk-123456789', $item->getField('token'));
        $this->assertSame('sk-123456789', $item->getSecret('token'));

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->assertTableColumnStateSet('token', 'sk****89', $item)
            ->mountAction(TestAction::make('edit')->table($item))
            ->assertSchemaStateSet(['token' => null]);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction(TestAction::make('edit')->table($item), ['label' => 'Post', 'token' => ''])
            ->assertHasNoActionErrors();

        $item->refresh();

        $this->assertSame('Post', $item->label());
        $this->assertSame('sk-123456789', $item->getSecret('token'));
    }

    public function test_settings_of_visible_characters_are_described(): void
    {
        $this->assertSame(
            [__('mutable-content-filament::ui.settings.describe.visible_start', ['count' => 4])],
            FieldTypeSettingsComponents::describe(Type::TYPE_SECRET, [TypeSettings::VISIBLE_START => 4]),
        );
    }
}
