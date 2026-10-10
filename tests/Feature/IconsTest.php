<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Filament\Support\Icons\Heroicon;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\Pages\ManageItems;
use Amarenkov\MutableContentFilament\Helpers\IconHelper;

use Amarenkov\MutableContentFilament\Tests\TestCase;

class IconsTest extends TestCase
{
    public function test_field_type_icons_are_not_put_into_the_core_registry(): void
    {
        $this->assertNull(app(LovRegistry::class)->getLovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));
    }

    public function test_field_type_icon_is_the_package_default_unless_set_in_the_admin_panel(): void
    {
        $this->assertSame(Heroicon::OutlinedHashtag, IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));

        $item = Lov::where('fields->code', Type::CLASS_CODE)->first()->items()->where('fields->code', Type::TYPE_INT)->first();

        $item->fill(['icon' => 'o-calculator']);
        $item->save();

        $this->assertSame(Heroicon::OutlinedCalculator, IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));

        $item->fill(['icon' => 'flame']);
        $item->save();

        $this->assertSame(Heroicon::OutlinedHashtag, IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));
    }

    public function test_items_table_shows_default_icons(): void
    {
        $lov = Lov::where('fields->code', Type::CLASS_CODE)->first();
        $item = $lov->items()->where('fields->code', Type::TYPE_INT)->first();

        Livewire::test(ManageItems::class, ['parentRecord' => $lov])
            ->assertTableColumnStateSet('icon', Heroicon::OutlinedHashtag->value, $item);
    }
}
