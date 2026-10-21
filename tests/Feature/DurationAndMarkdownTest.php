<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Forms\Components\MarkdownEditor;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;
use Amarenkov\MutableContent\ValueObjects\Duration;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldTypeSettingsComponents;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Pages\ManageFields;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;

use Amarenkov\MutableContentFilament\Tests\TestCase;

class DurationAndMarkdownTest extends TestCase
{
    protected function makeLov(string $fieldType, string $code, array $settings = []): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'job', 'label' => 'Job']);
        $lov->save();

        $field = new Field();
        $field->fill(array_filter(['code' => $code, 'label' => ucfirst($code), 'field_type' => $fieldType, 'field_type_settings' => $settings ?: null]));
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'job']);
        $usage->save();

        return $lov;
    }

    public function test_duration_is_entered_and_shown_in_the_display_unit(): void
    {
        $lov = $this->makeLov(Type::TYPE_DURATION, 'estimate', [TypeSettings::DISPLAY_UNIT => Duration::UNIT_HOUR]);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'deploy', 'label' => 'Deploy', 'estimate' => '1.5'])
            ->assertHasNoActionErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame(5400, $item->getField('estimate'));

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->assertTableColumnFormattedStateSet('estimate', '1.5 h', $item);
    }

    public function test_markdown_text_has_an_editor_and_a_plain_preview(): void
    {
        $lov = $this->makeLov(Type::TYPE_TEXT, 'notes', [TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN]);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->mountAction('create')
            ->assertFormComponentExists('notes', fn ($component) => $component instanceof MarkdownEditor)
            ->fillForm(['code' => 'deploy', 'label' => 'Deploy', 'notes' => "## Steps\n\n- **build**\n- ship"])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->assertTableColumnFormattedStateSet('notes', 'Steps build ship', Item::where('lov_id', $lov->id)->first());
    }

    public function test_format_setting_is_saved_from_the_field_form_and_described(): void
    {
        Livewire::test(ManageFields::class)
            ->mountAction('create')
            ->fillForm(['code' => 'notes', 'label' => 'Notes', 'field_type' => Type::TYPE_TEXT])
            ->set('mountedActions.0.data.'.FieldTypeSettingsComponents::FORMAT_STATE_PATH, TypeSettings::FORMAT_MARKDOWN)
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame([TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN], Field::where('code', 'notes')->first()->field_type_settings);
        $this->assertSame(['Markdown'], FieldTypeSettingsComponents::describe(Type::TYPE_TEXT, [TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN]));
    }
}
