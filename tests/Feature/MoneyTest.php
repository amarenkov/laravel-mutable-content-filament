<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Actions\Testing\TestAction;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Currency;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldTypeSettingsComponents;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;

use Amarenkov\MutableContentFilament\Tests\TestCase;

class MoneyTest extends TestCase
{
    protected function makeLov(string $fieldType, string $code, array $settings = []): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'product', 'label' => 'Product']);
        $lov->save();

        $field = new Field();
        $field->fill(array_filter(['code' => $code, 'label' => ucfirst($code), 'field_type' => $fieldType, 'field_type_settings' => $settings ?: null]));
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'product']);
        $usage->save();

        return $lov;
    }

    protected function addCurrency(string $code, string $label, string $symbol): void
    {
        $item = new Item();
        $item->fill([Item::FIELD_LOV_ID => Currency::instance()->id, 'code' => $code, 'label' => $label, Currency::ITEM_FIELD_DECIMALS => 2, Currency::ITEM_FIELD_SYMBOL => $symbol]);
        $item->save();
    }

    public function test_amount_and_currency_are_entered_and_shown(): void
    {
        $this->addCurrency('BYN', 'Belarusian ruble', 'Br');

        $lov = $this->makeLov(Type::TYPE_MONEY, 'price');

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'tea', 'label' => 'Tea', 'price' => ['amount' => '12.50', 'currency' => 'BYN']])
            ->assertHasNoActionErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame(['amount' => 12.5, 'currency' => 'BYN'], $item->price);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->assertTableColumnStateSet('price', "12.50\u{00A0}Br", $item)
            ->mountAction(TestAction::make('edit')->table($item))
            ->assertSchemaStateSet(['price' => ['amount' => 12.5, 'currency' => 'BYN']]);
    }

    public function test_empty_amount_is_no_value_and_decimals_are_validated(): void
    {
        $lov = $this->makeLov(Type::TYPE_MONEY, 'price');

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'tea', 'label' => 'Tea', 'price' => ['amount' => '1.005', 'currency' => 'USD']])
            ->assertHasActionErrors(['price.amount']);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'tea', 'label' => 'Tea', 'price' => ['amount' => null, 'currency' => 'USD']])
            ->assertHasNoActionErrors();

        $this->assertNull(Item::where('lov_id', $lov->id)->first()->price);
    }

    public function test_fixed_currency_and_sign_settings(): void
    {
        $this->addCurrency('BYN', 'Belarusian ruble', 'Br');

        $lov = $this->makeLov(Type::TYPE_MONEY, 'price', [TypeSettings::CURRENCY => 'BYN', TypeSettings::ALLOW_NEGATIVE => false]);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'tea', 'label' => 'Tea', 'price' => ['amount' => '-1']])
            ->assertHasActionErrors(['price.amount']);

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'tea', 'label' => 'Tea', 'price' => ['amount' => '3']])
            ->assertHasNoActionErrors();

        $this->assertSame(['amount' => 3, 'currency' => 'BYN'], Item::where('lov_id', $lov->id)->first()->price);
    }

    public function test_measurements_take_negative_values_when_allowed(): void
    {
        $lov = $this->makeLov(Type::TYPE_WEIGHT, 'net_weight');

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'a', 'label' => 'A', 'net_weight' => -2])
            ->assertHasActionErrors(['net_weight']);

        $field = Field::where('code', 'net_weight')->first();
        $field->field_type_settings = [TypeSettings::ALLOW_NEGATIVE => true];
        $field->save();

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->callAction('create', ['code' => 'a', 'label' => 'A', 'net_weight' => -2])
            ->assertHasNoActionErrors();

        $this->assertSame(-2.0, Item::where('lov_id', $lov->id)->first()->getWeight('net_weight')->kilograms);
    }

    public function test_settings_are_described(): void
    {
        $this->assertSame(['currency USD', 'allow negative', 'no 0'], FieldTypeSettingsComponents::describe(Type::TYPE_MONEY, [TypeSettings::CURRENCY => 'USD', TypeSettings::ALLOW_NEGATIVE => true, TypeSettings::ALLOW_ZERO => false]));
    }
}
